<?php
session_start();  

function getCurrentIpAddress() {
    // Get the current IP address of the server
    $ip = shell_exec("hostname -I");
    return trim($ip); // Trim whitespace
}

$current_ip = getCurrentIpAddress();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip_address = $_POST['ip_address'];
    $mode = $_POST['mode']; // Get mode selection

    // Send data to Flask API
    $url = "http://localhost:5000/change_ip"; // Replace with your Flask server's IP

    $data = json_encode(array("ip_address" => $ip_address, "mode" => $mode));

    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ),
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        $_SESSION['message'] = 'Error contacting the Flask API.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Decode the JSON response
    $response = json_decode($result, true);

    // Check response status
    if ($response['status'] === 'success') {
        $_SESSION['message'] = "IP address '$ip_address' updated successfully!";
        $_SESSION['ip_address'] = $ip_address; // Store IP for redirection
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['message'] = "Error: " . $response['message'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change IP Address</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            transition: transform 0.3s;
        }
        .container:hover {
            transform: scale(1.02);
        }
        .current-ip {
            margin-bottom: 20px;
            padding: 12px;
            background-color: #e7f3fe;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input[type="text"] {
            width: 90%;
            padding: 12px;
            margin-top: 8px;
            border: 2px solid #007bff;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus {
            border-color: #0056b3;
            outline: none;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            width: 100%;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        function toggleInput() {
            const modeSelect = document.getElementById('mode');
            const ipInput = document.getElementById('ip_address');
            ipInput.disabled = (modeSelect.value === 'dhcp');
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Change IP Address</h1>
        <div class="current-ip">Current IP Address: <strong><?php echo $current_ip; ?></strong></div>
        <form method="post" action="">
            <label for="mode">Select Mode:</label>
            <select id="mode" name="mode" onchange="toggleInput()">
                <option value="static">Static</option>
                <option value="dhcp">DHCP</option>
            </select>
            <label for="ip_address">New IP Address:</label>
            <input type="text" id="ip_address" name="ip_address" required placeholder="Enter new IP address">
            <input type="submit" value="Update IP">
        </form>

        <?php
        // Display message if set
        if (isset($_SESSION['message'])) {
            echo "<script>alert('" . $_SESSION['message'] . "');</script>";
            
            // Open the new IP address in a new window if the update was successful
            if (isset($_SESSION['ip_address'])) {
                $new_ip = $_SESSION['ip_address'];
                echo "<script>window.open('http://$new_ip', '_blank');</script>";
                unset($_SESSION['ip_address']); // Clear the IP after using
            }
            
            unset($_SESSION['message']); // Clear the message after displaying
        }
        ?>
    </div>
</body>
</html>
