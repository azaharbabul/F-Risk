<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

// Include required configuration files
require_once('config.php');
require_once('cvss.php');

/******************************
 * FUNCTION: DATABASE CONNECT *
 ******************************/
function db_open()
{
        // Connect to the database
        try
        {
                $db = new PDO("mysql:dbname=".DB_DATABASE.";host=".DB_HOSTNAME.";port=".DB_PORT,DB_USERNAME,DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

                return $db;
        }
        catch (PDOException $e)
        {
               // printf("A fatal error has occurred.  Please contact support.");
                die("Database Connection Failed: " . $e->getMessage());
        }

        return null;
}

/*********************************
 * FUNCTION: DATABASE DISCONNECT *
 *********************************/
function db_close($db)
{
        // Close the DB connection
        $db = null;
}

/*****************************
 * FUNCTION: STATEMENT DEBUG *
 *****************************/
function statement_debug($stmt)
{
	try
	{
		$stmt->execute();
	}
	catch (PDOException $e)
	{
		echo "ERROR: " . $e->getMessage();
	}
}

/***************************************
 * FUNCTION: GET DATABASE TABLE VALUES *
 ***************************************/
function get_table($name)
{
	// Open the database connection
	$db = db_open();

	// Query the database
	$stmt = $db->prepare("SELECT * FROM `$name` ORDER BY value");
	$stmt->execute();

	// Store the list in the array
	$array = $stmt->fetchAll();

	// Close the database connection
        db_close($db);

	return $array;
}

/***************************************
 * FUNCTION: GET TABLE ORDERED BY NAME *
 ***************************************/
function get_table_ordered_by_name($table_name)
{
        // Open the database connection
        $db = db_open();

	// Create the query statement
	$stmt = $db->prepare("SELECT * FROM `$table_name` ORDER BY name");

        // Execute the database query
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array;
}

/******************************
 * FUNCTION: GET CUSTOM TABLE *
 ******************************/
function get_custom_table($type)
{
        // Open the database connection
        $db = db_open();

	// Array of CVSS values
	$allowed_cvss_values = array('AccessComplexity', 'AccessVector', 'Authentication', 'AvailabilityRequirement', 'AvailImpact', 'CollateralDamagePotential', 'ConfidentialityRequirement', 'ConfImpact', 'Exploitability', 'IntegImpact', 'IntegrityRequirement', 'RemediationLevel', 'ReportConfidence', 'TargetDistribution');

	// If we want enabled users
	if ($type == "enabled_users")
	{
        	$stmt = $db->prepare("SELECT * FROM user WHERE enabled = 1 ORDER BY name");
	}
	// If we want disabled users
	else if ($type == "disabled_users")
	{
		$stmt = $db->prepare("SELECT * FROM user WHERE enabled = 0 ORDER BY name");
	}
	// If we want a CVSS scoring table
	else if (in_array($type, $allowed_cvss_values))
	{
		$stmt = $db->prepare("SELECT * FROM CVSS_scoring WHERE metric_name = :type ORDER BY id");
		$stmt->bindParam(":type", $type, PDO::PARAM_STR, 30);
	}

	// Execute the database query
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array;
}

/*****************************
 * FUNCTION: GET RISK LEVELS *
 *****************************/
function get_risk_levels()
{
	// Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("SELECT * FROM risk_levels ORDER BY value");
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array;
}

/*******************************
 * FUNCTION: GET REVIEW LEVELS *
 *******************************/
function get_review_levels()
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("SELECT * FROM review_levels ORDER BY value");
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array;
}

/********************************
 * FUNCTION: UPDATE RISK LEVELS *
 ********************************/
function update_risk_levels($high, $medium, $low)
{
        // Open the database connection
        $db = db_open();
 
        // Update the high risk level
        $stmt = $db->prepare("UPDATE risk_levels SET value=:value WHERE name='High'");
	$stmt->bindParam(":value", $high, PDO::PARAM_INT);
        $stmt->execute();

        // Update the medium risk level
        $stmt = $db->prepare("UPDATE risk_levels SET value=:value WHERE name='Medium'");
        $stmt->bindParam(":value", $medium, PDO::PARAM_INT);
        $stmt->execute();

        // Update the low risk level
        $stmt = $db->prepare("UPDATE risk_levels SET value=:value WHERE name='Low'");
        $stmt->bindParam(":value", $low, PDO::PARAM_INT);
        $stmt->execute();
        
        // Close the database connection
        db_close($db);
        
        return true;
}

/************************************
 * FUNCTION: UPDATE REVIEW SETTINGS *
 ************************************/
function update_review_settings($high, $medium, $low)
{
        // Open the database connection
        $db = db_open();

        // Update the high risk level
        $stmt = $db->prepare("UPDATE review_levels SET value=:value WHERE name='High'");
        $stmt->bindParam(":value", $high, PDO::PARAM_INT);
        $stmt->execute();

        // Update the medium risk level
        $stmt = $db->prepare("UPDATE review_levels SET value=:value WHERE name='Medium'");
        $stmt->bindParam(":value", $medium, PDO::PARAM_INT);
        $stmt->execute();

        // Update the low risk level
        $stmt = $db->prepare("UPDATE review_levels SET value=:value WHERE name='Low'");
        $stmt->bindParam(":value", $low, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/**********************************
 * FUNCTION: CREATE CVSS DROPDOWN *
 **********************************/
function create_cvss_dropdown($name, $selected = NULL, $blank = true)
{
	echo "<select id=\"" . htmlentities($name, ENT_QUOTES) . "\" name=\"" . htmlentities($name, ENT_QUOTES) . "\" class=\"form-field\" style=\"width:120px;\" onClick=\"javascript:showHelp('" . htmlentities($name, ENT_QUOTES) . "Help');\">\n";

        // If the blank is true
        if ($blank == true)
        {
                echo "    <option value=\"\">--</option>\n";
        }

        // Get the list of options
        $options = get_custom_table($name);

        // For each option
        foreach ($options as $option)
        {
		// Create the CVSS metric value
		$value = $option['abrv_metric_value'];

                // If the option is selected
                if ($selected == $value)
                {
                        $text = " selected";
                }
                else $text = "";

                echo "    <option value=\"" . $value . "\"" . $text . ">" . htmlentities($option['metric_value'], ENT_QUOTES) . "</option>\n";
        }

        echo "  </select>\n";
}

/************************************
 * FUNCTION: CREATE SELECT DROPDOWN *
 ************************************/
function create_dropdown($name, $selected = NULL, $rename = NULL, $blank = true)
{
	if ($rename != NULL)
	{
		echo "<select id=\"" . htmlentities($rename, ENT_QUOTES) . "\" name=\"" . htmlentities($rename, ENT_QUOTES) . "\">\n";
	}
	else echo "<select id=\"" . htmlentities($name, ENT_QUOTES) . "\" name=\"" . htmlentities($name, ENT_QUOTES) . "\">\n";

	// If the blank is true
	if ($blank == true)
	{
		echo "    <option value=\"\">--</option>\n";
	}

	// If we want a table that should be ordered by name instead of value
	if ($name == "user" || $name == "category" || $name == "team" || $name == "technology" || $name == "location")
	{
		$options = get_table_ordered_by_name($name);
	}
	// If we want a table of only enabled users
	else if ($name == "enabled_users")
	{
		$options = get_custom_table($name);
	}
	// If we want a table of only disabled users
	else if ($name == "disabled_users")
	{
		$options = get_custom_table($name);
	}
	// Otherwise
	else
	{
        	// Get the list of options
        	$options = get_table($name);
	}

        // For each option
        foreach ($options as $option)
        {
		// If the option is selected
		if ($selected == $option['value'])
		{
			$text = " selected";
		}
		else $text = "";

                echo "    <option value=\"" . $option['value'] . "\"" . $text . ">" . htmlentities($option['name'], ENT_QUOTES) . "</option>\n";
        }

	echo "  </select>\n";
}

/**************************************
 * FUNCTION: CREATE MULTIPLE DROPDOWN *
 **************************************/
function create_multiple_dropdown($name, $selected = NULL, $rename = NULL)
{
        if ($rename != NULL)
        {
                echo "<select multiple id=\"" . htmlentities($rename, ENT_QUOTES) . "\" name=\"" . htmlentities($rename, ENT_QUOTES) . "[]\">\n";
        }
        else echo "<select multiple id=\"" . htmlentities($name, ENT_QUOTES) . "\" name=\"" . htmlentities($name, ENT_QUOTES) . "[]\">\n";

	// Create all or none options
	echo "    <option value=\"all\">ALL</option>\n";
	echo "    <option value=\"none\">NONE</option>\n";

        // Get the list of options
        $options = get_table($name);

        // For each option
        foreach ($options as $option)
        {
		// Pattern is a team id surrounded by colons
		$regex_pattern = "/:" . $option['value'] .":/";

                // If the user belongs to the team or all was selected
                if (preg_match($regex_pattern, $selected, $matches) || $selected == "all")
                {
                        $text = " selected";
                }
                else $text = "";

                echo "    <option value=\"" . $option['value'] . "\"" . $text . ">" . htmlentities($option['name'], ENT_QUOTES) . "</option>\n";
        }

        echo "  </select>\n";
}

/*******************************
 * FUNCTION: CREATE RISK TABLE *
 *******************************/
function create_risk_table()
{
	$impacts = get_table("impact");
	$likelihoods = get_table("likelihood");

	// Create legend table
	echo "<table>\n";
	echo "<tr height=\"20px\">\n";
	echo "<td><div style=\"width:10px;height:10px;border:1px solid #000;background-color:red;\" /></td>\n";
	echo "<td>High Risk</td>\n";
	echo "<td>&nbsp;</td>\n";
	echo "<td><div style=\"width:10px;height:10px;border:1px solid #000;background-color:orange;\" /></td>\n";
	echo "<td>Medium Risk</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td><div style=\"width:10px;height:10px;border:1px solid #000;background-color:yellow;\" /></td>\n";
        echo "<td>Low Risk</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td><div style=\"width:10px;height:10px;border:1px solid #000;background-color:white;\" /></td>\n";
        echo "<td>Irrelevant</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

	echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"10\">\n";

	// For each impact level
	for ($i=4; $i>=0; $i--)
	{
		echo "<tr>\n";
	
		// If this is the first row add the y-axis label
		if ($i == 4)
		{
			echo "<td rowspan=\"5\"><div class=\"text-rotation\"><b>Impact</b></div></td>\n";
		}

		// Add the y-axis values
        	echo "<td bgcolor=\"silver\" height=\"50px\" width=\"100px\">" . htmlentities($impacts[$i]['name'], ENT_QUOTES) . "</td>\n";
        	echo "<td bgcolor=\"silver\" align=\"center\" height=\"50px\" width=\"100px\">" . $impacts[$i]['value'] . "</td>\n";

		// For each likelihood level
		for ($j=0; $j<=4; $j++)
		{
			// Calculate risk
			$risk = calculate_risk($impacts[$i]['value'], $likelihoods[$j]['value']);

			// Get the risk color
			$color = get_risk_color($risk);

			echo "<td align=\"center\" bgcolor=\"" . $color . "\" height=\"50px\" width=\"100px\">" . $risk . "</td>\n";
		}

		echo "</tr>\n";
	}

        echo "<tr>\n";
	echo "<td>&nbsp;</td>\n";
	echo "<td>&nbsp;</td>\n";
	echo "<td>&nbsp;</td>\n";

	// Add the x-axis values
	for ($x=0; $x<=4; $x++)
	{
		echo "<td align=\"center\" bgcolor=\"silver\" height=\"50px\" width=\"100px\">" . $likelihoods[$x]['value'] . "<br />" . htmlentities($likelihoods[$x]['name'], ENT_QUOTES) . "</td>\n";
	}

	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td>&nbsp;</td>\n";
        echo "<td>&nbsp;</td>\n";
        echo "<td>&nbsp;</td>\n";
	echo "<td colspan=\"5\" align=\"center\"><b>Likelihood</b></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

/****************************
 * FUNCTION: CALCULATE RISK *
 ****************************/
function calculate_risk($impact, $likelihood)
{
	// If the impact or likelihood are not a 1 to 5 value
	if (preg_match("/^[1-5]$/", $impact) && preg_match("/^[1-5]$/", $likelihood))
	{
		// Get risk_model
		$risk_model = get_setting("risk_model");

		// Pick the risk formula
		if ($risk_model == 1)
		{
			$max_risk = 35;
			$risk = ($likelihood * $impact) + (2 * $impact);
		}
		else if ($risk_model == 2)
		{
			$max_risk = 30;
			$risk = ($likelihood * $impact) + $impact;
		}
        	else if ($risk_model == 3)
        	{
			$max_risk = 25;
                	$risk = $likelihood * $impact;
        	}
        	else if ($risk_model == 4)
        	{
			$max_risk = 30;
                	$risk = ($likelihood * $impact) + $likelihood;
        	}
        	else if ($risk_model == 5)
        	{
			$max_risk = 35;
                	$risk = ($likelihood * $impact) + (2 * $likelihood);
        	}

		// This puts it on a 1 to 10 scale similar to CVSS
		$risk = round($risk * (10 / $max_risk), 1);
	}
	// If the impact or likelihood were not specified risk is 10
	else $risk = 10;

	return $risk;
}

/****************************
 * FUNCTION: GET RISK COLOR *
 ****************************/
function get_risk_color($risk)
{
        // Open the database connection
        $db = db_open();

        // Get the risk levels
        $stmt = $db->prepare("SELECT name FROM risk_levels WHERE value<=$risk ORDER BY value DESC LIMIT 1");
        $stmt->execute();

	// Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

	// Find the color
	if ($array[0]['name'] == "High")
	{
		$color = "red";
	}
	else if ($array[0]['name'] == "Medium")
	{
		$color = "orange";
	}
	else if ($array[0]['name'] == "Low")
	{
		$color = "yellow";
	}
	else $color = "white";

        return $color;
}

/*********************************
 * FUNCTION: GET RISK LEVEL NAME *
 *********************************/
function get_risk_level_name($risk)
{
	// If the risk is not null
	if ($risk != "")
	{
        	// Open the database connection
        	$db = db_open();
        	// Get the risk levels
        	$stmt = $db->prepare("SELECT name FROM risk_levels WHERE value<=$risk ORDER BY value DESC LIMIT 1");
        	$stmt->execute();

        	// Store the list in the array
        	$array = $stmt->fetchAll();

        	// Close the database connection
        	db_close($db);

		// If the risk is High, Medium, or Low
		if ($array[0]['name'] != "")
		{
			return $array[0]['name'];
		}
		// Otherwise the risk is Irrelevant
		else return "Irrelevant";
	}
	// Return a null value
	return "";
}

/*******************************
 * FUNCTION: UPDATE RISK MODEL *
 *******************************/
function update_risk_model($risk_model)
{
        // Open the database connection
        $db = db_open();

        // Get the risk levels
        $stmt = $db->prepare("UPDATE settings SET value=:risk_model WHERE name='risk_model'");
	$stmt->bindParam(":risk_model", $risk_model, PDO::PARAM_INT);
        $stmt->execute();

	// Get the list of all risks using the classic formula
	$stmt = $db->prepare("SELECT id, calculated_risk, CLASSIC_likelihood, CLASSIC_impact FROM risk_scoring WHERE scoring_method = 1");
	$stmt->execute();

        // Store the list in the risks array
        $risks = $stmt->fetchAll();

	// For each risk using the classic formula
	foreach ($risks as $risk)
	{
		$likelihood = $risk['CLASSIC_likelihood'];
		$impact = $risk['CLASSIC_impact'];

                // Calculate the risk via classic method
                $calculated_risk = calculate_risk($impact, $likelihood);

		// If the calculated risk is different than what is in the DB
		if ($calculated_risk != $risk['calculated_risk'])
		{
			// Update the value in the DB
			$stmt = $db->prepare("UPDATE risk_scoring SET calculated_risk = :calculated_risk WHERE id = :id");
			$stmt->bindParam(":calculated_risk", $calculated_risk, PDO::PARAM_INT);
			$stmt->bindParam(":id", $risk['id'], PDO::PARAM_INT);
			$stmt->execute();
		}
	}

        // Close the database connection
        db_close($db);

	return true;
}

/***********************************
 * FUNCTION: CHANGE SCORING METHOD *
 ***********************************/
function change_scoring_method($risk_id, $scoring_method)
{
        // Subtract 1000 from the risk_id
        $id = $risk_id - 1000;

        // Open the database connection
        $db = db_open();

	// Update the scoring method for the given risk ID
	$stmt = $db->prepare("UPDATE risk_scoring SET scoring_method = :scoring_method WHERE id = :id");
	$stmt->bindParam(":scoring_method", $scoring_method, PDO::PARAM_INT);
	$stmt->bindParam(":id", $id, PDO::PARAM_INT);
	$stmt->execute();

        // Close the database connection
        db_close($db);

	// Return the new scoring method
	return $scoring_method;
}

/**************************
 * FUNCTION: UPDATE TABLE *
 **************************/
function update_table($table, $name, $value)
{
        // Open the database connection
        $db = db_open();

        // Get the risk levels
        $stmt = $db->prepare("UPDATE $table SET name=:name WHERE value=:value");
        $stmt->bindParam(":name", $name, PDO::PARAM_STR, 20);
	$stmt->bindParam(":value", $value, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*************************
 * FUNCTION: GET SETTING *
 *************************/
function get_setting($setting)
{
        // Open the database connection
        $db = db_open();

        // Get the risk levels
        $stmt = $db->prepare("SELECT * FROM settings where name=:setting");
        $stmt->bindParam(":setting", $setting);
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

	$value = $array[0]['value'];

	return $value;
}

/***************************
 * FUNCTION: INSERT STRING *
 ***************************/
function add_name($table, $name, $size=20)
{
        // Open the database connection
        $db = db_open();

        // Get the risk levels
        $stmt = $db->prepare("INSERT INTO $table (`name`) VALUES (:name)");
        $stmt->bindParam(":name", $name, PDO::PARAM_STR, $size);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/**************************
 * FUNCTION: DELETE VALUE *
 **************************/
function delete_value($table, $value)
{
        // Open the database connection
        $db = db_open();

        // Delete the table value
        $stmt = $db->prepare("DELETE FROM $table WHERE value=:value");
        $stmt->bindParam(":value", $value, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*************************
 * FUNCTION: ENABLE USER *
 *************************/
function enable_user($value)
{
        // Open the database connection
        $db = db_open();

        // Set enabled = 1 for the user
        $stmt = $db->prepare("UPDATE user SET enabled = 1 WHERE value=:value");
        $stmt->bindParam(":value", $value, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/**************************
 * FUNCTION: DISABLE USER *
 **************************/
function disable_user($value)
{
        // Open the database connection
        $db = db_open();
        
        // Set enabled = 0 for the user
        $stmt = $db->prepare("UPDATE user SET enabled = 0 WHERE value=:value");
        $stmt->bindParam(":value", $value, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*****************************
 * FUNCTION: DOES USER EXIST *
 *****************************/
function user_exist($user)
{
        // Open the database connection
        $db = db_open();

        // Find the user
	$stmt = $db->prepare("SELECT * FROM user WHERE name=:user");
	$stmt->bindParam(":user", $user, PDO::PARAM_STR, 20);

        $stmt->execute();

	// Fetch the array
	$array = $stmt->fetchAll();

	// If the array is empty
	if (empty($array))
	{
		$return = false;
	}
	else $return = true;

        // Close the database connection
        db_close($db);

        return $return;
}

/**********************
 * FUNCTION: ADD USER *
 **********************/
function add_user($type, $user, $email, $name, $hash, $teams, $admin, $review_high, $review_medium, $review_low, $submit_risks, $modify_risks, $plan_mitigations)
{
        // Open the database connection
        $db = db_open();

        // Insert the new user
        $stmt = $db->prepare("INSERT INTO user (`type`, `username`, `name`, `email`, `password`, `teams`, `admin`, `review_high`, `review_medium`, `review_low`, `submit_risks`, `modify_risks`, `plan_mitigations`) VALUES (:type, :user, :name, :email, :hash, :teams, :admin, :review_high, :review_medium, :review_low, :submit_risks, :modify_risks, :plan_mitigations)");
	$stmt->bindParam(":type", $type, PDO::PARAM_STR, 20);
	$stmt->bindParam(":user", $user, PDO::PARAM_STR, 20);
	$stmt->bindParam(":name", $name, PDO::PARAM_STR, 50);
	$stmt->bindParam(":email", $email, PDO::PARAM_STR, 200);
	$stmt->bindParam(":hash", $hash, PDO::PARAM_STR, 60);
	$stmt->bindParam(":teams", $teams, PDO::PARAM_STR, 200);
        $stmt->bindParam(":admin", $admin, PDO::PARAM_INT);
	$stmt->bindParam(":review_high", $review_high, PDO::PARAM_INT);
	$stmt->bindParam(":review_medium", $review_medium, PDO::PARAM_INT);
	$stmt->bindParam(":review_low", $review_low, PDO::PARAM_INT);
	$stmt->bindParam(":submit_risks", $submit_risks, PDO::PARAM_INT);
	$stmt->bindParam(":modify_risks", $modify_risks, PDO::PARAM_INT);
	$stmt->bindParam(":plan_mitigations", $plan_mitigations, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*************************
 * FUNCTION: UPDATE USER *
 *************************/
function update_user($user_id, $name, $email, $teams, $admin, $review_high, $review_medium, $review_low, $submit_risks, $modify_risks, $plan_mitigations)
{
        // Open the database connection
        $db = db_open();

        // Update the user
        $stmt = $db->prepare("UPDATE user set `name`=:name, `email`=:email, `teams`=:teams, `admin`=:admin, `review_high`=:review_high, `review_medium`=:review_medium, `review_low`=:review_low, `submit_risks`=:submit_risks, `modify_risks`=:modify_risks, `plan_mitigations`=:plan_mitigations WHERE `value`=:user_id");
	$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":name", $name, PDO::PARAM_STR, 50);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR, 200);
	$stmt->bindParam(":teams", $teams, PDO::PARAM_STR, 200);
        $stmt->bindParam(":admin", $admin, PDO::PARAM_INT);
        $stmt->bindParam(":review_high", $review_high, PDO::PARAM_INT);
        $stmt->bindParam(":review_medium", $review_medium, PDO::PARAM_INT);
        $stmt->bindParam(":review_low", $review_low, PDO::PARAM_INT);
        $stmt->bindParam(":submit_risks", $submit_risks, PDO::PARAM_INT);
        $stmt->bindParam(":modify_risks", $modify_risks, PDO::PARAM_INT);
        $stmt->bindParam(":plan_mitigations", $plan_mitigations, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/****************************
 * FUNCTION: GET USER BY ID *
 ****************************/
function get_user_by_id($id)
{
	// Open the database connection
	$db = db_open();

	// Get the user information
	$stmt = $db->prepare("SELECT * FROM user WHERE value = :value");
	$stmt->bindParam(":value", $id, PDO::PARAM_INT);
	$stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

	// Close the database connection
	db_close($db);

	return $array[0];
}

/*****************************
 * FUNCTION: UPDATE PASSWORD *
 *****************************/
function update_password($user, $hash)
{
        // Open the database connection
        $db = db_open();

        // Update password
        $stmt = $db->prepare("UPDATE user SET password=:hash WHERE username=:user");
	$stmt->bindParam(":user", $user, PDO::PARAM_STR, 20);
	$stmt->bindParam(":hash", $hash, PDO::PARAM_STR, 60);
	$stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*************************
 * FUNCTION: SUBMIT RISK *
 *************************/
function submit_risk($status, $subject, $reference_id, $location, $category, $team, $technology, $owner, $manager, $assessment, $notes)
{
        // Open the database connection
        $db = db_open();

        // Add the risk
        $stmt = $db->prepare("INSERT INTO risks (`status`, `subject`, `reference_id`, `location`, `category`, `team`, `technology`, `owner`, `manager`, `assessment`, `notes`, `submitted_by`) VALUES (:status, :subject, :reference_id, :location, :category, :team, :technology, :owner, :manager, :assessment, :notes, :submitted_by)");
	$stmt->bindParam(":status", $status, PDO::PARAM_STR, 10);
        $stmt->bindParam(":subject", $subject, PDO::PARAM_STR, 100);
	$stmt->bindParam(":reference_id", $reference_id, PDO::PARAM_STR, 20);
	$stmt->bindParam(":location", $location, PDO::PARAM_INT);
	$stmt->bindParam(":category", $category, PDO::PARAM_INT);
	$stmt->bindParam(":team", $team, PDO::PARAM_INT);
	$stmt->bindParam(":technology", $technology, PDO::PARAM_INT);
	$stmt->bindParam(":owner", $owner, PDO::PARAM_INT);
	$stmt->bindParam(":manager", $manager, PDO::PARAM_INT);
	$stmt->bindParam(":assessment", $assessment, PDO::PARAM_STR);
	$stmt->bindParam(":notes", $notes, PDO::PARAM_STR);
	$stmt->bindParam(":submitted_by", $_SESSION['uid'], PDO::PARAM_INT);
        $stmt->execute();

	// Get the id of the risk
	$last_insert_id = $db->lastInsertId();

        // If notification is enabled
        if (notification_extra())
        {
                // Include the team separation extra
                require_once($_SERVER{'DOCUMENT_ROOT'} . "/extras/notification.php");

		// Send the notification
		notify_new_risk($last_insert_id, $subject);
        }

        // Close the database connection
        db_close($db);

        return $last_insert_id;
}

/************************************
 * FUNCTION: GET_CVSS_NUMERIC_VALUE *
 ************************************/
function get_cvss_numeric_value($abrv_metric_name, $abrv_metric_value)
{
        // Open the database connection
        $db = db_open();

	// Find the numeric value for the submitted metric
	$stmt = $db->prepare("SELECT numeric_value FROM CVSS_scoring WHERE abrv_metric_name = :abrv_metric_name AND abrv_metric_value = :abrv_metric_value");
	$stmt->bindParam(":abrv_metric_name", $abrv_metric_name, PDO::PARAM_STR, 3);
	$stmt->bindParam(":abrv_metric_value", $abrv_metric_value, PDO::PARAM_STR, 3);
	$stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();
	
        // Close the database connection
        db_close($db);

	// Return the numeric value found
	return $array[0]['numeric_value'];
}

/*********************************
 * FUNCTION: SUBMIT RISK SCORING *
 *********************************/
function submit_risk_scoring($last_insert_id, $scoring_method, $likelihood, $impact, $AccessVector, $AccessComplexity, $Authentication, $ConfImpact, $IntegImpact, $AvailImpact, $Exploitability, $RemediationLevel, $ReportConfidence, $CollateralDamagePotential, $TargetDistribution, $ConfidentialityRequirement, $IntegrityRequirement, $AvailabilityRequirement)
{
	// Open the database connection
        $db = db_open();

	// If the scoring method is classic (1)
	if ($scoring_method == 1)
	{
        	// Calculate the risk via classic method
        	$calculated_risk = calculate_risk($impact, $likelihood);
	}
	// If the scoring method is cvss (2)
	else if ($scoring_method == 2)
	{
        	// Get the numeric values for the CVSS submission
		$AccessVectorScore = get_cvss_numeric_value("AV", $AccessVector);
                $AccessComplexityScore = get_cvss_numeric_value("AC", $AccessComplexity);
                $AuthenticationScore = get_cvss_numeric_value("Au", $Authentication);
                $ConfImpactScore = get_cvss_numeric_value("C", $ConfImpact);
                $IntegImpactScore = get_cvss_numeric_value("I", $IntegImpact);
                $AvailImpactScore = get_cvss_numeric_value("A", $AvailImpact);
                $ExploitabilityScore = get_cvss_numeric_value("E", $Exploitability);
                $RemediationLevelScore = get_cvss_numeric_value("RL", $RemediationLevel);
                $ReportConfidenceScore = get_cvss_numeric_value("RC", $ReportConfidence);
                $CollateralDamagePotentialScore = get_cvss_numeric_value("CDP", $CollateralDamagePotential);
                $TargetDistributionScore = get_cvss_numeric_value("TD", $TargetDistribution);
                $ConfidentialityRequirementScore = get_cvss_numeric_value("CR", $ConfidentialityRequirement);
                $IntegrityRequirementScore = get_cvss_numeric_value("IR", $IntegrityRequirement);
                $AvailabilityRequirementScore = get_cvss_numeric_value("AR", $AvailabilityRequirement);

		// Calculate the risk via cvss method
	        $calculated_risk = calculate_cvss_score($AccessVectorScore, $AccessComplexityScore, $AuthenticationScore, $ConfImpactScore, $IntegImpactScore, $AvailImpactScore, $ExploitabilityScore, $RemediationLevelScore, $ReportConfidenceScore, $CollateralDamagePotentialScore, $TargetDistributionScore, $ConfidentialityRequirementScore, $IntegrityRequirementScore, $AvailabilityRequirementScore);
	}
	// Otherwise
	else
	{
		return false;
	}

        // Add the risk
        $stmt = $db->prepare("INSERT INTO risk_scoring (`id`, `scoring_method`, `calculated_risk`, `CLASSIC_likelihood`, `CLASSIC_impact`, `CVSS_AccessVector`, `CVSS_AccessComplexity`, `CVSS_Authentication`, `CVSS_ConfImpact`, `CVSS_IntegImpact`, `CVSS_AvailImpact`, `CVSS_Exploitability`, `CVSS_RemediationLevel`, `CVSS_ReportConfidence`, `CVSS_CollateralDamagePotential`, `CVSS_TargetDistribution`, `CVSS_ConfidentialityRequirement`, `CVSS_IntegrityRequirement`, `CVSS_AvailabilityRequirement`) VALUES (:last_insert_id, :scoring_method, :calculated_risk, :CLASSIC_likelihood, :CLASSIC_impact, :CVSS_AccessVector, :CVSS_AccessComplexity, :CVSS_Authentication, :CVSS_ConfImpact, :CVSS_IntegImpact, :CVSS_AvailImpact, :CVSS_Exploitability, :CVSS_RemediationLevel, :CVSS_ReportConfidence, :CVSS_CollateralDamagePotential, :CVSS_TargetDistribution, :CVSS_ConfidentialityRequirement, :CVSS_IntegrityRequirement, :CVSS_AvailabilityRequirement)");
	$stmt->bindParam(":last_insert_id", $last_insert_id, PDO::PARAM_INT);
        $stmt->bindParam(":scoring_method", $scoring_method, PDO::PARAM_STR, 10);
        $stmt->bindParam(":calculated_risk", $calculated_risk, PDO::PARAM_STR);
        $stmt->bindParam(":CLASSIC_likelihood", $likelihood, PDO::PARAM_INT);
        $stmt->bindParam(":CLASSIC_impact", $impact, PDO::PARAM_INT);
	$stmt->bindParam(":CVSS_AccessVector", $AccessVector, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_AccessComplexity", $AccessComplexity, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_Authentication", $Authentication, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_ConfImpact", $ConfImpact, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_IntegImpact", $IntegImpact, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_AvailImpact", $AvailImpact, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_Exploitability", $Exploitability, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_RemediationLevel", $RemediationLevel, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_ReportConfidence", $ReportConfidence, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_CollateralDamagePotential", $CollateralDamagePotential, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_TargetDistribution", $TargetDistribution, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_ConfidentialityRequirement", $ConfidentialityRequirement, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_IntegrityRequirement", $IntegrityRequirement, PDO::PARAM_STR, 3);
	$stmt->bindParam(":CVSS_AvailabilityRequirement", $AvailabilityRequirement, PDO::PARAM_STR, 3);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*********************************
 * FUNCTION: UPDATE RISK SCORING *
 *********************************/
function update_risk_scoring($id, $scoring_method, $likelihood, $impact, $AccessVector, $AccessComplexity, $Authentication, $ConfImpact, $IntegImpact, $AvailImpact, $Exploitability, $RemediationLevel, $ReportConfidence, $CollateralDamagePotential, $TargetDistribution, $ConfidentialityRequirement, $IntegrityRequirement, $AvailabilityRequirement)
{
        // Subtract 1000 from the id
        $id = $id - 1000;

        // Open the database connection
        $db = db_open();

        // If the scoring method is classic (1)
        if ($scoring_method == 1)
        {
                // Calculate the risk via classic method
                $calculated_risk = calculate_risk($impact, $likelihood);
        }
        // If the scoring method is cvss (2)
        else if ($scoring_method == 2)
        {
                // Get the numeric values for the CVSS submission
                $AccessVectorScore = get_cvss_numeric_value("AV", $AccessVector);
                $AccessComplexityScore = get_cvss_numeric_value("AC", $AccessComplexity);
                $AuthenticationScore = get_cvss_numeric_value("Au", $Authentication);
                $ConfImpactScore = get_cvss_numeric_value("C", $ConfImpact);
                $IntegImpactScore = get_cvss_numeric_value("I", $IntegImpact);
                $AvailImpactScore = get_cvss_numeric_value("A", $AvailImpact);
                $ExploitabilityScore = get_cvss_numeric_value("E", $Exploitability);
                $RemediationLevelScore = get_cvss_numeric_value("RL", $RemediationLevel);
                $ReportConfidenceScore = get_cvss_numeric_value("RC", $ReportConfidence);
                $CollateralDamagePotentialScore = get_cvss_numeric_value("CDP", $CollateralDamagePotential);
                $TargetDistributionScore = get_cvss_numeric_value("TD", $TargetDistribution);
                $ConfidentialityRequirementScore = get_cvss_numeric_value("CR", $ConfidentialityRequirement);
                $IntegrityRequirementScore = get_cvss_numeric_value("IR", $IntegrityRequirement);
                $AvailabilityRequirementScore = get_cvss_numeric_value("AR", $AvailabilityRequirement);

                // Calculate the risk via cvss method
                $calculated_risk = calculate_cvss_score($AccessVectorScore, $AccessComplexityScore, $AuthenticationScore, $ConfImpactScore, $IntegImpactScore, $AvailImpactScore, $ExploitabilityScore, $RemediationLevelScore, $ReportConfidenceScore, $CollateralDamagePotentialScore, $TargetDistributionScore, $ConfidentialityRequirementScore, $IntegrityRequirementScore, $AvailabilityRequirementScore);
        }
        // Otherwise
        else
        {
                return false;
        }

        // Update the risk
        $stmt = $db->prepare("UPDATE risk_scoring SET scoring_method=:scoring_method, calculated_risk=:calculated_risk, CLASSIC_likelihood=:CLASSIC_likelihood, CLASSIC_impact=:CLASSIC_impact, CVSS_AccessVector=:CVSS_AccessVector, CVSS_AccessComplexity=:CVSS_AccessComplexity, CVSS_Authentication=:CVSS_Authentication, CVSS_ConfImpact=:CVSS_ConfImpact, CVSS_IntegImpact=:CVSS_IntegImpact, CVSS_AvailImpact=:CVSS_AvailImpact, CVSS_Exploitability=:CVSS_Exploitability, CVSS_RemediationLevel=:CVSS_RemediationLevel, CVSS_ReportConfidence=:CVSS_ReportConfidence, CVSS_CollateralDamagePotential=:CVSS_CollateralDamagePotential, CVSS_TargetDistribution=:CVSS_TargetDistribution, CVSS_ConfidentialityRequirement=:CVSS_ConfidentialityRequirement, CVSS_IntegrityRequirement=:CVSS_IntegrityRequirement, CVSS_AvailabilityRequirement=:CVSS_AvailabilityRequirement WHERE id=:id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":scoring_method", $scoring_method, PDO::PARAM_STR, 10);
        $stmt->bindParam(":calculated_risk", $calculated_risk, PDO::PARAM_STR);
        $stmt->bindParam(":CLASSIC_likelihood", $likelihood, PDO::PARAM_INT);
        $stmt->bindParam(":CLASSIC_impact", $impact, PDO::PARAM_INT);
        $stmt->bindParam(":CVSS_AccessVector", $AccessVector, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_AccessComplexity", $AccessComplexity, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_Authentication", $Authentication, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_ConfImpact", $ConfImpact, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_IntegImpact", $IntegImpact, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_AvailImpact", $AvailImpact, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_Exploitability", $Exploitability, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_RemediationLevel", $RemediationLevel, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_ReportConfidence", $ReportConfidence, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_CollateralDamagePotential", $CollateralDamagePotential, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_TargetDistribution", $TargetDistribution, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_ConfidentialityRequirement", $ConfidentialityRequirement, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_IntegrityRequirement", $IntegrityRequirement, PDO::PARAM_STR, 3);
        $stmt->bindParam(":CVSS_AvailabilityRequirement", $AvailabilityRequirement, PDO::PARAM_STR, 3);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return $calculated_risk;
}

/*******************************
 * FUNCTION: SUBMIT MITIGATION *
 *******************************/
function submit_mitigation($risk_id, $status, $planning_strategy, $mitigation_effort, $current_solution, $security_requirements, $security_recommendations)
{
        // Subtract 1000 from id
        $risk_id = (int)$risk_id - 1000;

        // Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();
        
        // Add the mitigation
        $stmt = $db->prepare("INSERT INTO mitigations (`risk_id`, `planning_strategy`, `mitigation_effort`, `current_solution`, `security_requirements`, `security_recommendations`, `submitted_by`) VALUES (:risk_id, :planning_strategy, :mitigation_effort, :current_solution, :security_requirements, :security_recommendations, :submitted_by)");

        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
        $stmt->bindParam(":planning_strategy", $planning_strategy, PDO::PARAM_INT);
	$stmt->bindParam(":mitigation_effort", $mitigation_effort, PDO::PARAM_INT);
	$stmt->bindParam(":current_solution", $current_solution, PDO::PARAM_STR);
	$stmt->bindParam(":security_requirements", $security_requirements, PDO::PARAM_STR);
	$stmt->bindParam(":security_recommendations", $security_recommendations, PDO::PARAM_STR);
	$stmt->bindParam(":submitted_by", $_SESSION['uid'], PDO::PARAM_INT);
        $stmt->execute();

	// Get the new mitigation id
	$mitigation_id = get_mitigation_id($risk_id);

	// Update the risk status and last_update
	$stmt = $db->prepare("UPDATE risks SET status=:status, last_update=:last_update, mitigation_id=:mitigation_id WHERE id = :risk_id");
	$stmt->bindParam(":status", $status, PDO::PARAM_STR, 20);
	$stmt->bindParam(":last_update", $current_datetime, PDO::PARAM_STR, 20);
	$stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
	$stmt->bindParam(":mitigation_id", $mitigation_id, PDO::PARAM_INT);

	$stmt->execute();

        // If notification is enabled
        if (notification_extra())
        {
                // Include the team separation extra
                require_once($_SERVER{'DOCUMENT_ROOT'} . "/extras/notification.php");

		// Send the notification
		notify_new_mitigation($risk_id);
        }

        // Close the database connection
        db_close($db);

        return $current_datetime;
}

/**************************************
 * FUNCTION: SUBMIT MANAGEMENT REVIEW *
 **************************************/
function submit_management_review($risk_id, $status, $review, $next_step, $reviewer, $comments)
{
        // Subtract 1000 from id
        $risk_id = (int)$risk_id - 1000;

        // Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();

        // Add the review
        $stmt = $db->prepare("INSERT INTO mgmt_reviews (`risk_id`, `review`, `reviewer`, `next_step`, `comments`) VALUES (:risk_id, :review, :reviewer, :next_step, :comments)");

        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
	$stmt->bindParam(":review", $review, PDO::PARAM_INT);
	$stmt->bindParam(":reviewer", $reviewer, PDO::PARAM_INT);
	$stmt->bindParam(":next_step", $next_step, PDO::PARAM_INT);
	$stmt->bindParam(":comments", $comments, PDO::PARAM_STR);

        $stmt->execute();

        // Get the new mitigation id
        $mgmt_review = get_review_id($risk_id);

        // Update the risk status and last_update
        $stmt = $db->prepare("UPDATE risks SET status=:status, last_update=:last_update, review_date=:review_date, mgmt_review=:mgmt_review WHERE id = :risk_id");
        $stmt->bindParam(":status", $status, PDO::PARAM_STR, 20);
        $stmt->bindParam(":last_update", $current_datetime, PDO::PARAM_STR, 20);
	$stmt->bindParam(":review_date", $current_datetime, PDO::PARAM_STR, 20);
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
        $stmt->bindParam(":mgmt_review", $mgmt_review, PDO::PARAM_INT);

        $stmt->execute();

        // If notification is enabled
        if (notification_extra())
        {
                // Include the team separation extra
                require_once($_SERVER{'DOCUMENT_ROOT'} . "/extras/notification.php");

		// Send the notification
		notify_new_review($risk_id);
        }

        // Close the database connection
        db_close($db);

        return true;
}

/*************************
 * FUNCTION: UPDATE RISK *
 *************************/
function update_risk($id, $subject, $reference_id, $location, $category, $team, $technology, $owner, $manager, $assessment, $notes)
{
	// Subtract 1000 from id
	$id = $id - 1000;

	// Get current datetime for last_update
	$current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();

        // Update the risk
	$stmt = $db->prepare("UPDATE risks SET subject=:subject, reference_id=:reference_id, location=:location, category=:category, team=:team, technology=:technology, owner=:owner, manager=:manager, assessment=:assessment, notes=:notes, last_update=:date WHERE id = :id");

	$stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":subject", $subject, PDO::PARAM_STR, 100);
	$stmt->bindParam(":reference_id", $reference_id, PDO::PARAM_STR, 20);
	$stmt->bindParam(":location", $location, PDO::PARAM_INT);
        $stmt->bindParam(":category", $category, PDO::PARAM_INT);
        $stmt->bindParam(":team", $team, PDO::PARAM_INT);
        $stmt->bindParam(":technology", $technology, PDO::PARAM_INT);
        $stmt->bindParam(":owner", $owner, PDO::PARAM_INT);
        $stmt->bindParam(":manager", $manager, PDO::PARAM_INT);
        $stmt->bindParam(":assessment", $assessment, PDO::PARAM_STR);
        $stmt->bindParam(":notes", $notes, PDO::PARAM_STR);
	$stmt->bindParam(":date", $current_datetime, PDO::PARAM_STR);
        $stmt->execute();

        // If notification is enabled
        if (notification_extra())
        {
                // Include the team separation extra
                require_once($_SERVER{'DOCUMENT_ROOT'} . "/extras/notification.php");

		// Send the notification
		notify_risk_update($id);
        }

        // Close the database connection
        db_close($db);

        return true;
}

/************************
 * FUNCTION: CONVERT ID *
 ************************/
function convert_id($id)
{
	// Add 1000 to any id to make it at least 4 digits
	$id = $id + 1000;

	return $id;
}

/****************************
 * FUNCTION: GET RISK BY ID *
 ****************************/
function get_risk_by_id($id)
{
        // Open the database connection
        $db = db_open();

	// Subtract 1000 from the id
	$id = $id - 1000;

        // Query the database
	$stmt = $db->prepare("SELECT a.*, b.* FROM risk_scoring a INNER JOIN risks b on a.id = b.id WHERE b.id=:id LIMIT 1");
	$stmt->bindParam(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array;
}

/**********************************
 * FUNCTION: GET MITIGATION BY ID *
 **********************************/
function get_mitigation_by_id($risk_id)
{
        // Open the database connection
        $db = db_open();

        // Subtract 1000 from the id
        $risk_id = $risk_id - 1000;

        // Query the database
        $stmt = $db->prepare("SELECT * FROM mitigations WHERE risk_id=:risk_id");
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);

        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

	// If the array is empty
	if (empty($array))
	{
		return false;
	}
        else return $array;
}

/******************************
 * FUNCTION: GET REVIEW BY ID *
 ******************************/
function get_review_by_id($risk_id)
{
        // Open the database connection
        $db = db_open();

        // Subtract 1000 from the id
        $risk_id = $risk_id - 1000;

        // Query the database
        $stmt = $db->prepare("SELECT * FROM mgmt_reviews WHERE risk_id=:risk_id ORDER BY submission_date DESC");
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);

        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        // If the array is empty
        if (empty($array))
        {
                return false;
        }
        else return $array;
}

/***********************
 * FUNCTION: GET RISKS *
 ***********************/
function get_risks($sort_order=0)
{
        // Open the database connection
        $db = db_open();

	// If this is the default, sort by risk
	if ($sort_order == 0)
	{
        	// Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id WHERE status != \"Closed\" ORDER BY calculated_risk DESC");
        	$stmt->execute();

        	// Store the list in the array
        	$array = $stmt->fetchAll();
	}

	// 1 = Show risks requiring mitigations
	else if ($sort_order == 1)
	{
		// Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id WHERE mitigation_id = 0 AND status != \"Closed\" ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
	}

        // 2 = Show risks requiring management review
        else if ($sort_order == 2)
        {
                // Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id WHERE mgmt_review = 0 AND status != \"Closed\" ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

	// 3 = Show risks by review date
	else if ($sort_order == 3)
	{
		// Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id WHERE status != \"Closed\" ORDER BY review_date ASC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
	}

	// 4 = Show risks that are closed
	else if ($sort_order == 4)
        {
		// Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id WHERE status = \"Closed\" ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

	// 5 = Show open risks that should be considered for projects
	else if ($sort_order == 5)
	{
		// Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id RIGHT JOIN (SELECT c1.risk_id, next_step, date FROM mgmt_reviews c1 RIGHT JOIN (SELECT risk_id, MAX(submission_date) AS date FROM mgmt_reviews GROUP BY risk_id) AS c2 ON c1.risk_id = c2.risk_id AND c1.submission_date = c2.date WHERE next_step = 2) AS c ON a.id = c.risk_id WHERE status != \"Closed\" ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

	// 6 = Show open risks accepted until next review
	else if ($sort_order == 6)
	{
		// Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id RIGHT JOIN (SELECT c1.risk_id, next_step, date FROM mgmt_reviews c1 RIGHT JOIN (SELECT risk_id, MAX(submission_date) AS date FROM mgmt_reviews GROUP BY risk_id) AS c2 ON c1.risk_id = c2.risk_id AND c1.submission_date = c2.date WHERE next_step = 1) AS c ON a.id = c.risk_id WHERE status != \"Closed\" ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
	}

	// 7 = Show open risks to submit as production issues
	else if ($sort_order == 7)
	{
		// Query the database
                $stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id RIGHT JOIN (SELECT c1.risk_id, next_step, date FROM mgmt_reviews c1 RIGHT JOIN (SELECT risk_id, MAX(submission_date) AS date FROM mgmt_reviews GROUP BY risk_id) AS c2 ON c1.risk_id = c2.risk_id AND c1.submission_date = c2.date WHERE next_step = 3) AS c ON a.id = c.risk_id WHERE status != \"Closed\" ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
	}

        // 8 = Show all open risks assigned to this user by risk level
        else if ($sort_order == 8)
        {
                // Query the database
                $stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a LEFT JOIN risks b ON a.id = b.id WHERE status != \"Closed\" AND (owner = :uid OR manager = :uid) ORDER BY calculated_risk DESC");
		$stmt->bindParam(":uid", $_SESSION['uid'], PDO::PARAM_INT);
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

	// 9 = Show open risks scored by CVSS Scoring
	else if ($sort_order == 9)
	{
                // Query the database
                $stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a JOIN risks b ON a.id = b.id JOIN risk_scoring c on b.id = c.id WHERE b.status != \"Closed\" AND c.scoring_method = 2 ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
	}

        // 10 = Show open risks scored by Classic Scoring
        else if ($sort_order == 10)
        {
                // Query the database
		$stmt = $db->prepare("SELECT a.calculated_risk, b.* FROM risk_scoring a JOIN risks b ON a.id = b.id JOIN risk_scoring c on b.id = c.id WHERE b.status != \"Closed\" AND c.scoring_method = 1 ORDER BY calculated_risk DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

        // 11 = Show All Risks by Date Submitted
        else if ($sort_order == 11)
        {
                // Query the database
                $stmt = $db->prepare("SELECT a.calculated_risk, b.id, b.subject, b.status, b.submission_date, d.name AS team, c.name FROM risk_scoring a JOIN risks b ON a.id = b.id LEFT JOIN user c ON b.submitted_by = c.value LEFT JOIN team d ON b.team = d.value ORDER BY DATE(b.submission_date) DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

        // 12 = Show management reviews by date
        else if ($sort_order == 12)
        {
                // Query the database
                $stmt = $db->prepare("SELECT a.subject, a.id, b.submission_date, c.name, d.name AS review, e.name AS next_step FROM risks a JOIN mgmt_reviews b ON a.id = b.risk_id JOIN user c ON b.reviewer = c.value LEFT JOIN review d ON b.review = d.value LEFT JOIN next_step e ON b.next_step = e.value ORDER BY DATE(b.submission_date) DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

        // 13 = Show mitigations by date
        else if ($sort_order == 13)
        {
                // Query the database
                $stmt = $db->prepare("SELECT a.subject, a.id, b.submission_date, c.name, d.name AS planning_strategy, e.name AS mitigation_effort FROM risks a JOIN mitigations b ON a.id = b.risk_id JOIN user c ON b.submitted_by = c.value LEFT JOIN planning_strategy d ON b.planning_strategy = d.value LEFT JOIN mitigation_effort e ON b.mitigation_effort = e.value ORDER BY DATE(b.submission_date) DESC");
                $stmt->execute();

                // Store the list in the array
                $array = $stmt->fetchAll();
        }

        // Close the database connection
        db_close($db);

	// If team separation is enabled
	if (team_separation_extra())
	{
		// Include the team separation extra
		require_once($_SERVER{'DOCUMENT_ROOT'} . "/extras/team_separation.php");

		// Strip out risks the user should not have access to
		$array = strip_no_access_risks($array);
	}

        return $array;
}

/****************************
 * FUNCTION: GET RISK TABLE *
 ****************************/
function get_risk_table($sort_order=0)
{
        // Get risks
        $risks = get_risks($sort_order);

	echo "<table class=\"table table-bordered table-condensed sortable\">\n";
	echo "<thead>\n";
	echo "<tr>\n";
	echo "<th align=\"left\" width=\"50px\">ID</th>\n";
	echo "<th align=\"left\" width=\"150px\">Status</th>\n";
	echo "<th align=\"left\" width=\"300px\">Subject</th>\n";
	echo "<th align=\"center\" width=\"100px\">Risk</th>\n";
	echo "<th align=\"center\" width=\"150px\">Submitted</th>\n";
	echo "<th align=\"center\" width=\"100px\">Mitigation Planned</th>\n";
	echo "<th align=\"center\" width=\"100px\">Management Review</th>\n";
	echo "</tr>\n";
	echo "</thead>\n";
	echo "<tbody>\n";

	// For each risk
	foreach ($risks as $risk)
	{
		// Get the risk color
		$color = get_risk_color($risk['calculated_risk']);

		echo "<tr>\n";
		echo "<td align=\"left\" width=\"50px\"><a href=\"../management/view.php?id=" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "\">" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "</a></td>\n";
		echo "<td align=\"left\" width=\"150px\">" . htmlentities($risk['status'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"left\" width=\"300px\">" . htmlentities(stripslashes($risk['subject']), ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" bgcolor=\"" . $color . "\" width=\"100px\">" . htmlentities($risk['calculated_risk'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['submission_date'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" width=\"100px\">" . planned_mitigation(htmlentities(convert_id($risk['id']), ENT_QUOTES), htmlentities($risk['mitigation_id'], ENT_QUOTES)) . "</td>\n";
		echo "<td align=\"center\" width=\"100px\">" . management_review(htmlentities(convert_id($risk['id']), ENT_QUOTES), htmlentities($risk['mgmt_review'], ENT_QUOTES)) . "</td>\n";
		echo "</tr>\n";
	}

	echo "</tbody>\n";
	echo "</table>\n";

	return true;
}

/***************************************
 * FUNCTION: GET SUBMITTED RISKS TABLE *
 ***************************************/
function get_submitted_risks_table($sort_order=11)
{
        // Get risks
        $risks = get_risks($sort_order);

        echo "<table class=\"table table-bordered table-condensed sortable\">\n";
        echo "<thead>\n";
        echo "<tr>\n";
        echo "<th align=\"left\" width=\"50px\">ID</th>\n";
        echo "<th align=\"left\" width=\"300px\">Subject</th>\n";
	echo "<th align=\"center\" width=\"150px\">Submission Date</th>\n";
        echo "<th align=\"left\" width=\"150px\">Calculated Risk</th>\n";
        echo "<th align=\"left\" width=\"150px\">Status</th>\n";
        echo "<th align=\"center\" width=\"150px\">Team</th>\n";
        echo "<th align=\"center\" width=\"150px\">Submitted By</th>\n";
        echo "</tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";

        // For each risk
        foreach ($risks as $risk)
        {
                // Get the risk color
                $color = get_risk_color($risk['calculated_risk']);

                echo "<tr>\n";
                echo "<td align=\"left\" width=\"50px\"><a href=\"../management/view.php?id=" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "\">" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "</a></td>\n";
                echo "<td align=\"left\" width=\"300px\">" . htmlentities(stripslashes($risk['subject']), ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['submission_date'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" bgcolor=\"" . $color . "\" width=\"150px\">" . htmlentities($risk['calculated_risk'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['status'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['team'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['name'], ENT_QUOTES) . "</td>\n";
                echo "</tr>\n";
        }

        echo "</tbody>\n";
        echo "</table>\n";

        return true;
}

/***********************************
 * FUNCTION: GET MITIGATIONS TABLE *
 ***********************************/
function get_mitigations_table($sort_order=13)
{
        // Get risks
        $risks = get_risks($sort_order);

        echo "<table class=\"table table-bordered table-condensed sortable\">\n";
        echo "<thead>\n";
        echo "<tr>\n";
        echo "<th align=\"left\" width=\"50px\">ID</th>\n";
        echo "<th align=\"left\" width=\"300px\">Subject</th>\n";
        echo "<th align=\"left\" width=\"150px\">Mitigation Date</th>\n";
        echo "<th align=\"left\" width=\"150px\">Planning Strategy</th>\n";
        echo "<th align=\"center\" width=\"150px\">Mitigation Effort</th>\n";
        echo "<th align=\"center\" width=\"150px\">Submitted By</th>\n";
        echo "</tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";

        // For each risk
        foreach ($risks as $risk)
        {
                echo "<tr>\n";
                echo "<td align=\"left\" width=\"50px\"><a href=\"../management/view.php?id=" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "\">" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "</a></td>\n";
                echo "<td align=\"left\" width=\"300px\">" . htmlentities(stripslashes($risk['subject']), ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['submission_date'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['planning_strategy'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['mitigation_effort'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['name'], ENT_QUOTES) . "</td>\n";
                echo "</tr>\n";
        }

        echo "</tbody>\n";
        echo "</table>\n";

        return true;
}

/*************************************
 * FUNCTION: GET REVIEWED RISK TABLE *
 *************************************/
function get_reviewed_risk_table($sort_order=12)
{
        // Get risks
        $risks = get_risks($sort_order);

        echo "<table class=\"table table-bordered table-condensed sortable\">\n";
        echo "<thead>\n";
        echo "<tr>\n";
        echo "<th align=\"left\" width=\"50px\">ID</th>\n";
	echo "<th align=\"left\" width=\"300px\">Subject</th>\n";
        echo "<th align=\"left\" width=\"150px\">Review Date</th>\n";
        echo "<th align=\"left\" width=\"150px\">Review</th>\n";
        echo "<th align=\"center\" width=\"150px\">Next Step</th>\n";
	echo "<th align=\"center\" width=\"150px\">Reviewer</th>\n";
        echo "</tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";

        // For each risk
        foreach ($risks as $risk)
        {
                echo "<tr>\n";
                echo "<td align=\"left\" width=\"50px\"><a href=\"../management/view.php?id=" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "\">" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "</a></td>\n";
                echo "<td align=\"left\" width=\"300px\">" . htmlentities(stripslashes($risk['subject']), ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['submission_date'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['review'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['next_step'], ENT_QUOTES) . "</td>\n";
		echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['name'], ENT_QUOTES) . "</td>\n";
                echo "</tr>\n";
        }

        echo "</tbody>\n";
        echo "</table>\n";

        return true;
}

/******************************************
 * FUNCTION: GET PROJECTS AND RISKS TABLE *
 ******************************************/
function get_projects_and_risks_table()
{
	// Get projects
	$projects = get_projects();

	// For each project
	foreach ($projects as $project)
	{
                $id = (int)$project['value'];
                $name = htmlentities(stripslashes($project['name']), ENT_QUOTES);
                $order = (int)$project['order'];

                // If the project is not 0 (ie. Unassigned Risks)
                if ($id != 0)
                {
        		echo "<table class=\"table table-bordered table-condensed sortable\">\n";
        		echo "<thead>\n";
        		echo "<tr>\n";
        		echo "<th bgcolor=\"#0088CC\" colspan=\"4\"><center><font color=\"#FFFFFF\">" . $name . "</font></center></th>\n";
        		echo "</tr>\n";
		        echo "<tr>\n";
        		echo "<th align=\"left\" width=\"50px\">ID</th>\n";
        		echo "<th align=\"left\" width=\"300px\">Subject</th>\n";
        		echo "<th align=\"left\" width=\"100px\">Risk</th>\n";
        		echo "<th align=\"left\" width=\"150px\">Date Submitted</th>\n";
        		echo "</tr>\n";
        		echo "</thead>\n";
        		echo "<tbody>\n";

        		// Get risks marked as consider for projects
        		$risks = get_risks(5);

                	// For each risk
                	foreach ($risks as $risk)
                	{
                        	$subject = htmlentities(stripslashes($risk['subject']), ENT_QUOTES);
                        	$risk_id = (int)$risk['id'];
                        	$project_id = (int)$risk['project_id'];
                        	$color = get_risk_color($risk['calculated_risk']);

                        	// If the risk is assigned to that project id
                        	if ($id == $project_id)
                        	{
					echo "<tr>\n";
                			echo "<td align=\"left\" width=\"50px\"><a href=\"../management/view.php?id=" . convert_id($risk_id) . "\">" . convert_id($risk_id) . "</a></td>\n";
                			echo "<td align=\"left\" width=\"300px\">" . $subject . "</td>\n";
                			echo "<td align=\"center\" bgcolor=\"" . $color . "\" width=\"100px\">" . htmlentities($risk['calculated_risk'], ENT_QUOTES) . "</td>\n";
                			echo "<td align=\"center\" width=\"150px\">" . htmlentities($risk['submission_date'], ENT_QUOTES) . "</td>\n";
					echo "</tr>\n";
				}
			}

			echo "</tbody>\n";
			echo "</table>\n";
			echo "<br />\n";
		}
	}

}

/******************************
 * FUNCTION: GET PROJECT LIST *
 ******************************/
function get_project_list()
{
        // Get projects
        $projects = get_projects();

	echo "<form action=\"\" method=\"post\">\n";
	echo "<input type=\"submit\" name=\"update_order\" value=\"Update\" /><br /><br />\n";
	echo "<ul id=\"prioritize\">\n";

        // For each project
        foreach ($projects as $project)
        {
		$id = $project['value'];
		$name = $project['name'];
		$order = $project['order'];

		// If the project is not 0 (ie. Unassigned Risks)
		if ($id != 0)
		{
			echo "<li class=\"ui-state-default\" id=\"sort_" . $id . "\">\n";
			echo "<span>&#x21C5;</span>&nbsp;" . htmlentities($name, ENT_QUOTES) . "\n";
			echo "<input type=\"hidden\" id=\"order" . $id . "\" name=\"order_" . $id . "\" value=\"" . $order . "\" />\n";
			echo "<input type=\"hidden\" name=\"ids[]\" value=\"" . $id . "\" />\n";
			echo "</li>\n";
		}
	}

	echo "</ul>\n";
	echo "<br /><input type=\"submit\" name=\"update_order\" value=\"Update\" />\n";
	echo "</form>\n";

	return true;
}

/********************************
 * FUNCTION: GET PROJECT STATUS *
 ********************************/
function get_project_status()
{
        // Get projects
        $projects = get_projects();

	echo "<form action=\"\" method=\"post\">\n";
	echo "<div id=\"tabs\">\n";
	echo "<ul>\n";
        echo "<li><a href=\"#tabs-0\">Active Projects</li>\n";
        echo "<li><a href=\"#tabs-1\">On Hold Projects</li>\n";
        echo "<li><a href=\"#tabs-2\">Completed Projects</li>\n";
        echo "<li><a href=\"#tabs-3\">Cancelled Projects</li>\n";
	echo "</ul>\n";
	echo "<div id=\"tabs-0\">\n";
	echo "<ul id=\"sortable-0\" class=\"connectedSortable ui-helper-reset\">\n";
	// PROJECTS GO HERE
	echo "</ul>\n";
	echo "</div>\n";
        echo "<div id=\"tabs-1\">\n";
        echo "<ul id=\"sortable-1\" class=\"connectedSortable ui-helper-re
set\">\n";
        // PROJECTS GO HERE
        echo "</ul>\n";
        echo "</div>\n";
        echo "<div id=\"tabs-2\">\n";
        echo "<ul id=\"sortable-2\" class=\"connectedSortable ui-helper-re
set\">\n";
        // PROJECTS GO HERE
        echo "</ul>\n";
        echo "</div>\n";
        echo "<div id=\"tabs-3\">\n";
        echo "<ul id=\"sortable-3\" class=\"connectedSortable ui-helper-re
set\">\n";
        // PROJECTS GO HERE
        echo "</ul>\n";
        echo "</div>\n";
	echo "</div>\n";
	echo "<br /><input type=\"submit\" name=\"update_project_status\" value=\"Update Project Statuses\" />\n";
	echo "</form>\n";

/*
	foreach ($projects as $project)
        {
                $id = $project['value'];
                $name = $project['name'];

		echo "<li class=\"project\">" . $name . "</li>\n";
	}
*/

        return true;
}

/**********************************
 * FUNCTION: UPDATE PROJECT ORDER *
 **********************************/
function update_project_order($order, $id)
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("UPDATE projects SET `order` = :order WHERE `value` = :id");
	$stmt->bindParam(":order", $order, PDO::PARAM_INT);
	$stmt->bindParam(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        // Close the database connection
        db_close($db);

	return true;
}

/*********************************
 * FUNCTION: UPDATE RISK PROJECT *
 *********************************/
function update_risk_project($project_id, $risk_id)
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("UPDATE risks SET `project_id` = :project_id WHERE `id` = :risk_id");
        $stmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);

        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/******************************
 * FUNCTION: GET PROJECT TABS *
 ******************************/
function get_project_tabs()
{
	$projects = get_projects();

	echo "<form action=\"\" method=\"post\">\n";
	echo "<div id=\"tabs\">\n";
	echo "<ul>\n";
	
	foreach ($projects as $project)
	{
		$id = $project['value'];
		$name = $project['name'];

		echo "<li><a href=\"#tabs-" . $id . "\">" . htmlentities($name, ENT_QUOTES) . "</a></li>\n";
	}

	echo "</ul>\n";

        // Get risks marked as consider for projects
        $risks = get_risks(5);

	// For each project
	foreach ($projects as $project)
	{
		$id = $project['value'];
		$name = $project['name'];

		echo "<div id=\"tabs-" . $id . "\">\n";
		echo "<ul id=\"sortable-" . $id . "\" class=\"connectedSortable ui-helper-reset\">\n";

		// For each risk
		foreach ($risks as $risk)
		{
			$subject = stripslashes($risk['subject']);
			$risk_id = $risk['id'];
			$project_id = $risk['project_id'];
                	$color = get_risk_color($risk['calculated_risk']);

			// If the risk is assigned to that project id
			if ($id == $project_id)
			{
				echo "<li id=\"" . $risk_id . "\" class=\"" . $color . "\">" . htmlentities($subject, ENT_QUOTES) . "\n";
				echo "<input class=\"assoc-risk-with-project\" type=\"hidden\" id=\"risk" . $risk_id . "\" name=\"risk_" . $risk_id . "\" value=\"" . $project_id . "\" />\n";
                        	echo "<input id=\"all-risk-ids\" class=\"all-risk-ids\" type=\"hidden\" name=\"ids[]\" value=\"" . $risk_id . "\" />\n";
                        	echo "</li>\n";
			}
		}

		echo "</ul>\n";
		echo "</div>\n";
	}

	echo "</div>\n";
	echo "<br /><input type=\"submit\" name=\"update_projects\" value=\"Save Risks to Projects\" />\n";
	echo "</form>\n";
}

/**************************
 * FUNCTION: GET PROJECTS *
 **************************/
function get_projects()
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("SELECT * FROM projects ORDER BY `order` ASC");

        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();
        
        // Close the database connection
        db_close($db);

        return $array;
}

/*******************************
 * FUNCTION: PROJECT HAS RISKS *
 *******************************/
function project_has_risks($project_id)
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("SELECT * FROM risks WHERE project_id = :project_id");
	$stmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // If the array is empty
        if (empty($array))
        {
                $return = false;
        }
        else $return = true;

        // Close the database connection
        db_close($db);

        return $return;
}

/*******************************
 * FUNCTION: GET REVIEWS TABLE *
 *******************************/
function get_reviews_table($sort_order=3)
{
        // Get risks
        $risks = get_risks($sort_order);

        echo "<table class=\"table table-bordered table-condensed sortable\">\n";
        echo "<thead>\n";
        echo "<tr>\n";
        echo "<th align=\"left\" width=\"50px\">ID</th>\n";
        echo "<th align=\"left\" width=\"150px\">Status</th>\n";
        echo "<th align=\"left\" width=\"300px\">Subject</th>\n";
        echo "<th align=\"center\" width=\"100px\">Risk</th>\n";
        echo "<th align=\"center\" width=\"100px\">Days Open</th>\n";
        echo "<th align=\"center\" width=\"150px\">Next Review Date</th>\n";
        echo "</tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";

        // For each risk
        foreach ($risks as $risk)
        {
                // Get the risk color
                $color = get_risk_color($risk['calculated_risk']);

                echo "<tr>\n";
                echo "<td align=\"left\" width=\"50px\"><a href=\"../management/view.php?id=" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "\">" . htmlentities(convert_id($risk['id']), ENT_QUOTES) . "</a></td>\n";
                echo "<td align=\"left\" width=\"150px\">" . htmlentities($risk['status'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"left\" width=\"300px\">" . htmlentities(stripslashes($risk['subject']), ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" bgcolor=\"" . $color . "\" width=\"100px\">" . htmlentities($risk['calculated_risk'], ENT_QUOTES) . "</td>\n";
                echo "<td align=\"center\" width=\"100px\">" . dayssince($risk['submission_date']) . "</td>\n";
                echo "<td align=\"center\" width=\"150px\">" . next_review($color, $risk['id']) . "</td>\n";
                echo "</tr>\n";
        }

        echo "</tbody>\n";
        echo "</table>\n";

        return true;
}

/*******************************
 * FUNCTION: MANAGEMENT REVIEW *
 *******************************/
function management_review($risk_id, $mgmt_review)
{
	// If the review hasn't happened
	if ($mgmt_review == "0")
	{
		$value = "<a href=\"../management/mgmt_review.php?id=" . $risk_id . "\">No</a>";
	}
	else $value = "Yes";

	return $value;
}

/********************************
 * FUNCTION: PLANNED MITIGATION *
 ********************************/
function planned_mitigation($risk_id, $mitigation_id)
{
        // If the review hasn't happened
        if ($mitigation_id == "0")
        {
                $value = "<a href=\"../management/mitigate.php?id=" . $risk_id . "\">No</a>";
        }
        else $value = "Yes";

        return $value;
}

/*******************************
 * FUNCTION: GET NAME BY VALUE *
 *******************************/
function get_name_by_value($table, $value)
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("SELECT name FROM $table WHERE value=:value LIMIT 1");   
        $stmt->bindParam(":value", $value, PDO::PARAM_INT);

        $stmt->execute();
        
        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

	// If we get a value back from the query
	if (isset($array[0]['name']))
	{
		// Return that value
        	return stripslashes($array[0]['name']);
	}
	// Otherwise, return an empty string
	else return "";
} 

/***************************
 * FUNCTION: GET CVSS NAME *
 ***************************/
function get_cvss_name($metric_name, $abrv_metric_value)
{
        // Open the database connection
        $db = db_open();

        // Query the database
        $stmt = $db->prepare("SELECT metric_value FROM CVSS_scoring WHERE metric_name=:metric_name AND abrv_metric_value=:abrv_metric_value LIMIT 1");
	$stmt->bindParam(":metric_name", $metric_name, PDO::PARAM_STR, 30);
        $stmt->bindParam(":abrv_metric_value", $abrv_metric_value, PDO::PARAM_STR, 3);

        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        // If we get a value back from the query
        if (isset($array[0]['metric_value']))
        {
                // Return that value
		return stripslashes($array[0]['metric_value']);
        }
        // Otherwise, return an empty string
        else return "";
}

/*******************************
 * FUNCTION: GET MITIGATION ID *
 *******************************/
function get_mitigation_id($risk_id)
{       
        // Open the database connection
        $db = db_open();
        
        // Query the database
        $stmt = $db->prepare("SELECT id FROM mitigations WHERE risk_id=:risk_id");
	$stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
        $stmt->execute();

	// Store the list in the array
        $array = $stmt->fetchAll();
        
        // Close the database connection
        db_close($db);
        
        return $array[0]['id'];
}

/********************************
 * FUNCTION: GET MGMT REVIEW ID *
 ********************************/
function get_review_id($risk_id)
{
        // Open the database connection
        $db = db_open();

        // Query the database
	// Get the most recent management review id
        $stmt = $db->prepare("SELECT id FROM mgmt_reviews WHERE risk_id=:risk_id ORDER BY submission_date DESC LIMIT 1");
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array[0]['id'];
}

/*****************************
 * FUNCTION: DAYS SINCE DATE *
 *****************************/
function dayssince($date)
{
	$datetime1 = new DateTime($date);
	$datetime2 = new DateTime("now");
	$days = $datetime1->diff($datetime2);

	// Return the number of days
	return $days->format('%a');
}

/**********************************
 * FUNCTION: GET LAST REVIEW DATE *
 **********************************/
function get_last_review($risk_id)
{
        // Open the database connection
        $db = db_open();

        // Select the last submission date
	$stmt = $db->prepare("SELECT submission_date FROM mgmt_reviews WHERE risk_id=:risk_id ORDER BY submission_date DESC LIMIT 1");
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        // If the array is empty
        if (empty($array))
        {
                return "";
        }
        else return $array[0]['submission_date'];
}

/**********************************
 * FUNCTION: GET NEXT REVIEW DATE *
 **********************************/
function next_review($color, $risk_id)
{
	// Get the last review for this risk
	$last_review = get_last_review($risk_id);

	// If the risk is unreviewed
	if ($last_review == "")
	{
		$text = "UNREVIEWED";
	}
	else
	{
		// Get the review levels
		$review_levels = get_review_levels();

		// If high risk
		if ($color == "red")
		{
			// Get days to review high risks
			$days = $review_levels[0]['value'];
		}
		// If medium risk
		else if ($color == "orange")
		{
                        // Get days to review medium risks
                        $days = $review_levels[1]['value'];
		}
		// If low risk
		else if ($color == "yellow")
		{
                        // Get days to review low risks
                        $days = $review_levels[2]['value'];
		}

		// Next review date
                $last_review = new DateTime($last_review);
                $next_review = $last_review->add(new DateInterval('P'.$days.'D'));

		// If the next review date is after today
		if (strtotime($next_review->format('Y-m-d')) > time())
		{
                	$text = $next_review->format('Y-m-d');
		}
		else $text = "PAST DUE";
	}

	// Add the href tag to make it HTML
	$html = "<a href=\"../management/mgmt_review.php?id=" . convert_id($risk_id) . "\">" . $text . "</a>";

	// Return the HTML code
	return $html;
}

/************************
 * FUNCTION: CLOSE RISK *
 ************************/
function close_risk($id, $user_id, $status, $close_reason, $note)
{
        // Subtract 1000 from id
        $id = $id - 1000;

        // Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();

        // Add the closure
        $stmt = $db->prepare("INSERT INTO closures (`risk_id`, `user_id`, `close_reason`, `note`) VALUES (:risk_id, :user_id, :close_reason, :note)");

        $stmt->bindParam(":risk_id", $id, PDO::PARAM_INT);
	$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":close_reason", $close_reason, PDO::PARAM_INT);
        $stmt->bindParam(":note", addslashes($note), PDO::PARAM_STR);

        $stmt->execute();

        // Get the new mitigation id
        $close_id = get_close_id($id);

        // Update the risk
        $stmt = $db->prepare("UPDATE risks SET status=:status,last_update=:date,close_id=:close_id WHERE id = :id");

        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status, PDO::PARAM_STR, 50);
        $stmt->bindParam(":date", $current_datetime, PDO::PARAM_STR);
	$stmt->bindParam(":close_id", $close_id, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/**************************
 * FUNCTION: GET CLOSE ID *
 **************************/
function get_close_id($risk_id)
{
        // Open the database connection
        $db = db_open();

        // Query the database
        // Get the close id
        $stmt = $db->prepare("SELECT id FROM closures WHERE risk_id=:risk_id ORDER BY closure_date DESC LIMIT 1");
        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
        $stmt->execute();

        // Store the list in the array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        return $array[0]['id'];
}

/*************************
 * FUNCTION: REOPEN RISK *
 *************************/
function reopen_risk($id)
{
        // Subtract 1000 from id
        $id = $id - 1000;

        // Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();

        // Update the risk
        $stmt = $db->prepare("UPDATE risks SET status=\"Reopened\",last_update=:date,close_id=\"0\" WHERE id = :id");

        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":date", $current_datetime, PDO::PARAM_STR);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/*************************
 * FUNCTION: ADD COMMENT *
 *************************/
function add_comment($id, $user_id, $comment)
{
        // Subtract 1000 from id
        $id = $id - 1000;

        // Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');
        
        // Open the database connection
        $db = db_open();
        
        // Add the closure
        $stmt = $db->prepare("INSERT INTO comments (`risk_id`, `user`, `comment`) VALUES (:risk_id, :user, :comment)");
        
        $stmt->bindParam(":risk_id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":user", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $comment, PDO::PARAM_STR);

        $stmt->execute();
        
        // Update the risk
        $stmt = $db->prepare("UPDATE risks SET last_update=:date WHERE id = :id");

        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":date", $current_datetime, PDO::PARAM_STR);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        return true;
}

/**************************
 * FUNCTION: GET COMMENTS *
 **************************/
function get_comments($id)
{
        // Subtract 1000 from id
	$id = $id - 1000;

        // Open the database connection
        $db = db_open();

        // Get the comments
        $stmt = $db->prepare("SELECT a.date, a.comment, b.name FROM comments a LEFT JOIN user b ON a.user = b.value WHERE risk_id=:risk_id ORDER BY date DESC");

        $stmt->bindParam(":risk_id", $id, PDO::PARAM_INT);

        $stmt->execute();

        // Store the list in the array
        $comments = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        foreach ($comments as $comment)
        {
		$text = htmlentities(stripslashes($comment['comment']), ENT_QUOTES);
		$date = htmlentities(date('Y-m-d g:i A T', strtotime($comment['date'])), ENT_QUOTES);
		$user = htmlentities(stripslashes($comment['name']), ENT_QUOTES);

		echo "<p>".$date." > ".$user.": ".$text."</p>\n";
	}

        return true;
}

/*****************************
 * FUNCTION: GET AUDIT TRAIL *
 *****************************/
function get_audit_trail($id = NULL)
{
	// Open the database connection
	$db = db_open();

	// If the ID is not NULL
	if ($id != NULL)
	{
        	// Subtract 1000 from id
        	$id = $id - 1000;

        	// Get the comments for this specific ID
        	$stmt = $db->prepare("SELECT timestamp, message FROM audit_log WHERE risk_id=:risk_id ORDER BY timestamp DESC");

        	$stmt->bindParam(":risk_id", $id, PDO::PARAM_INT);
	}
	// Otherwise get the full audit trail
	else
	{
		$stmt = $db->prepare("SELECT timestamp, message FROM audit_log ORDER BY timestamp DESC");
	}

        $stmt->execute();
        
        // Store the list in the array
        $logs = $stmt->fetchAll();
        
        // Close the database connection
        db_close($db);
        
        foreach ($logs as $log)
        {       
                $text = htmlentities(stripslashes($log['message']), ENT_QUOTES);
                $date = htmlentities(date('Y-m-d g:i A T', strtotime($log['timestamp'])), ENT_QUOTES); 
                
                echo "<p>".$date." > ".$text."</p>\n";
        }

        return true;
}

/*******************************
 * FUNCTION: UPDATE MITIGATION *
 *******************************/
function update_mitigation($id, $planning_strategy, $mitigation_effort, $current_solution, $security_requirements, $security_recommendations)
{
        // Subtract 1000 from id
        $id = $id - 1000;

        // Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();

        // Update the risk
	$stmt = $db->prepare("UPDATE mitigations SET last_update=:date, planning_strategy=:planning_strategy, mitigation_effort=:mitigation_effort, current_solution=:current_solution, security_requirements=:security_requirements, security_recommendations=:security_recommendations WHERE risk_id=:id");

        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
	$stmt->bindParam(":date", $current_datetime, PDO::PARAM_STR);
        $stmt->bindParam(":planning_strategy", $planning_strategy, PDO::PARAM_INT);
	$stmt->bindParam(":mitigation_effort", $mitigation_effort, PDO::PARAM_INT);
        $stmt->bindParam(":current_solution", $current_solution, PDO::PARAM_STR);
        $stmt->bindParam(":security_requirements", $security_requirements, PDO::PARAM_STR);
	$stmt->bindParam(":security_recommendations", $security_recommendations, PDO::PARAM_STR);
        $stmt->execute();

        // If notification is enabled
        if (notification_extra())
        {
                // Include the team separation extra
                require_once($_SERVER{'DOCUMENT_ROOT'} . "/extras/notification.php");

		// Send the notification
		notify_mitigation_update($id);
        }

        // Close the database connection
        db_close($db);

        return $current_datetime;
}

/**************************
 * FUNCTION: GET REVIEWS *
 **************************/
function get_reviews($id)
{
        // Subtract 1000 from id
        $id = $id - 1000;

        // Open the database connection
        $db = db_open();

        // Get the comments
        $stmt = $db->prepare("SELECT * FROM mgmt_reviews WHERE risk_id=:risk_id ORDER BY submission_date DESC");

        $stmt->bindParam(":risk_id", $id, PDO::PARAM_INT);

        $stmt->execute();

        // Store the list in the array
        $reviews = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        foreach ($reviews as $review)
        {
                $date = htmlentities(date('Y-m-d g:i A T', strtotime($review['submission_date'])), ENT_QUOTES);
		$reviewer =  get_name_by_value("user", $review['reviewer']);
		$review_value = get_name_by_value("review", $review['review']);
		$next_step = get_name_by_value("next_step", $review['next_step']);
		$comment = htmlentities(stripslashes($review['comments']), ENT_QUOTES);

		echo "<p>\n";
		echo "<u>".$date."</u><br />\n";
		echo "Reviewer: ".$reviewer."<br />\n";
		echo "Review: ".$review_value."<br />\n";
		echo "Next Step: ".$next_step."<br />\n";
		echo "Comment: ".$comment."\n";
		echo "</p>\n";
        }

        return true;
}

/****************************
 * FUNCTION: LATEST VERSION *
 ****************************/
function latest_version($param)
{
	$version_page = file('https://www.dropbox.com/s/n1xzyhceom95rk9/Current_Version.xml');
 
	if ($param == "app")
	{
		$regex_pattern = "/<appversion>(.*)<\/appversion>/";
	}
	else if ($param == "db")
	{
		$regex_pattern = "/<dbversion>(.*)<\/dbversion>/";
	}

	foreach ($version_page as $line)
	{           
        	if (preg_match($regex_pattern, $line, $matches)) 
        	{
                	$latest_version = $matches[1];
        	}   
	}      

	// Return the latest version HTML encoded
	return htmlentities($latest_version, ENT_QUOTES);
}

/*****************************
 * FUNCTION: CURRENT VERSION *
 *****************************/
function current_version($param)
{
        if ($param == "app")
        {
		require_once('../includes/version.php');

		return APP_VERSION;
        }
        else if ($param == "db")
        {
		// Open the database connection
		$db = db_open();

		$stmt = $db->prepare("SELECT * FROM settings WHERE name=\"db_version\"");

		// Execute the statement
        	$stmt->execute();
        
       		// Get the current version
        	$array = $stmt->fetchAll();

        	// Close the database connection
        	db_close($db);

		// Return the current version
		return $array[0]['value'];
	}
}

/***********************
 * FUNCTION: WRITE LOG *
 ***********************/
function write_log($risk_id, $user_id, $message)
{
        // Subtract 1000 from id
        $risk_id = $risk_id - 1000;

        // Open the database connection
        $db = db_open();

        // Get the comments
        $stmt = $db->prepare("INSERT INTO audit_log (risk_id, user_id, message) VALUES (:risk_id, :user_id, :message)");

        $stmt->bindParam(":risk_id", $risk_id, PDO::PARAM_INT);
	$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
	$stmt->bindParam(":message", $message, PDO::PARAM_STR);

        $stmt->execute();

        // Close the database connection
        db_close($db);
}

/*******************************
 * FUNCTION: UPDATE LAST LOGIN *
 *******************************/
function update_last_login($user_id)
{
	// Get current datetime for last_update
        $current_datetime = date('Y-m-d H:i:s');

        // Open the database connection
        $db = db_open();

        // Update the last login
        $stmt = $db->prepare("UPDATE user SET `last_login`=:last_login WHERE `value`=:user_id");
	$stmt->bindParam(":last_login", $current_datetime, PDO::PARAM_STR, 20);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Close the database connection
        db_close($db);

	return true;
}

/*******************************
 * FUNCTION: GET ANNOUNCEMENTS *
 *******************************/
function get_announcements()
{
	$announcements = "<ul>\n";

        $announcement_file = file('https://www.dropbox.com/s/5z3o8iuj9yrtri9/announcements.xml');

	$regex_pattern = "/<announcement>(.*)<\/announcement>/";

        foreach ($announcement_file as $line)
        {
                if (preg_match($regex_pattern, $line, $matches))
                {
                        $announcements .= "<li>" . htmlentities($matches[1], ENT_QUOTES) . "</li>\n";
                }
        }

	$announcements .= "</ul>";

        // Return the announcement
        return $announcements;
}

/*****************************************
 * FUNCTION: CUSTOM AUTHENTICATION EXTRA *
 *****************************************/
function custom_authentication_extra()
{
        // Open the database connection
        $db = db_open();

        // Update the last login
        $stmt = $db->prepare("SELECT `value` FROM `settings` WHERE `name` = 'custom_auth'");
        $stmt->execute();

        // Get the results array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        // If no value was found
        if (empty($array))
        {
                return false;
        }
        // If the value is true
        else if ($array[0]['value'] == true)
        {
                return true;
        }
        else return false;
}

/***********************************
 * FUNCTION: TEAM SEPARATION EXTRA *
 ***********************************/
function team_separation_extra()
{
        // Open the database connection
        $db = db_open();

        // Update the last login
        $stmt = $db->prepare("SELECT `value` FROM `settings` WHERE `name` = 'team_separation'");
        $stmt->execute();

        // Get the results array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        // If no value was found
	if (empty($array))
	{
		return false;
	}
	// If the value is true
	else if ($array[0]['value'] == true)
	{
		return true;
	}
	else return false;
}

/********************************
 * FUNCTION: NOTIFICATION EXTRA *
 ********************************/
function notification_extra()
{
        // Open the database connection
        $db = db_open();

        // Update the last login
        $stmt = $db->prepare("SELECT `value` FROM `settings` WHERE `name` = 'notifications'");
        $stmt->execute();

        // Get the results array
        $array = $stmt->fetchAll();

        // Close the database connection
        db_close($db);

        // If no value was found
        if (empty($array))
        {
                return false;
        }
        // If the value is true
        else if ($array[0]['value'] == true)
        {
                return true;
        }
        else return false;
}

?>
