<?php


# Database class, providing a wrapper to database functions, but contains no application logic
class database
{
	# Class properties
	private $connection = NULL;
	private $errors = array ();
	
	
	# Function to connect to the database
	public function __construct ($hostname, $username, $password, $database)
	{
		# Connect to the database as the specified user, or end
		if (!$this->connection = @mysqli_connect ($hostname, $username, $password)) {
			$this->errors[] = "Error opening database connection with the database username '<strong>" . htmlspecialchars ($username) . "</strong>'. The database server said: '<em>" . htmlspecialchars (mysqli_connect_error ()) . "</em>'";
			return;		// End
		}
		
		# Ensure we are talking in Unicode, or end
		if (!mysqli_query ($this->connection, "SET NAMES 'utf8';")) {
			$this->errors[] = "Error setting the database connection to UTF-8";
			return;		// End
		}
		
		# Select the database, or end
		if (!@mysqli_select_db ($this->connection, $database)) {
			$this->errors[] = "Error selecting the database '<strong>" . htmlspecialchars ($database) . "</strong>'. Check it exists and that the user {$username} has rights to it. The database server said: '<em>" . htmlspecialchars (mysqli_error ($this->connection)) . "</em>'";
			$this->connection = NULL;
			return;		// End
		}
		
	}
	
	
	# Function to return the connection status
	public function isConnected ()
	{
		return ($this->connection);
	}
	
	
	# Getter to obtain the raw connection
	public function getConnection ()
	{
		return $this->connection;
	}
	
	
	# Getter to return errors
	public function getErrors ()
	{
		return $this->errors;
	}
	
	
	# Function to close the database explicitly
	public function close ()
	{
		# Explicitly close the database connection so that it cannot be reused
		if ($this->connection) {
			mysqli_close ($this->connection);
			$this->connection = NULL;
		}
	}
	
	
	# Generalised function to get data from an SQL query and return it as an array
	#!# Add failures as an explicit return false; this is not insecure at present though as array() will be retured (equating to boolean false), with the calling code then stopping execution in each case
	public function getData ($query)
	{
		# Create an empty array to hold the data
		$data = array ();
		
		# Execute the query or return false on failure
		if ($result = mysqli_query ($this->connection, $query)) {
			
			# Check that the table contains data
			if (mysqli_num_rows ($result) > 0) {
				
				# Loop through each row and add the data to it
				while ($row = mysqli_fetch_assoc ($result)) {
					$data[] = $row;
				}
			}
		}
		
		# Return the array
		return $data;
	}
	
	
	# Function to get one row
	public function getOne ($query)
	{
		# Get the data (indexed numerically), or end
		if (!$data = $this->getData ($query)) {return false;}
		
		# Ensure there is only one row
		if (count ($data) != 1) {return false;}
		
		# Return the first row
		return $data[0];
	}
	
	
	# Function to create a table from a list of fields
	public function createTable ($name, $fields)
	{
		# Construct the list of fields
		$fieldsSql = array ();
		foreach ($fields as $fieldname => $specification) {
			$fieldsSql[] = "{$fieldname} {$specification}";
		}
		
		# Compile the overall SQL; type is deliberately set to InnoDB so that rows are physically stored in the unique key order
		$query = "CREATE TABLE `{$name}` (" . implode (', ', $fieldsSql) . ") ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		
		# Create the table
		if (!mysqli_query ($this->connection, $query)) {
			$this->errors[] = "There was a problem setting up the {$name} table.";
			return false;
		}
		
		# Signal success
		return true;
	}
	
	
	# Function to obtain a list of tables in a database
	#!# Add failures as an explicit return false; this is not insecure at present though as array() will be retured (equating to boolean false), with the calling code then stopping execution in each case
	public function getTables ($database)
	{
		# Create a list of tables, alphabetically ordered, and put the result into an array
		$query = "SHOW TABLES FROM `{$database}`;";
		
		# Start a list of tables
		$tables = array ();
		
		# Get the tables
		if (!$tablesList = mysqli_query ($this->connection, $query)) {
			return $tables;
		}
		
		# Loop through the table resource to get the list of tables
		while ($tableDetails = mysqli_fetch_row ($tablesList)) {
			$tables[] = $tableDetails[0];
		}
		
		# Return the list of tables as an array
		return $tables;
	}
	
}

?>
