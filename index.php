<?php
/*
 Get OPENAI_API_KEY
 here (accessToken): https://chat.openai.com/api/auth/session
 or here (API key): https://platform.openai.com/account/api-keys
*/

define('OPENAI_API_KEY', '');

function openai($message){
  
  $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';
  $headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY
  ];
  $data = [
    "prompt" => $message,
    'max_tokens' => 150,
    "temperature" => 0.9,
  ];
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response  = curl_exec($ch);
  curl_close($ch);
  $arr = json_decode($response, 1);

  $return['message'] = '';
  if(!empty($arr['error'])){
    $return['message'] = '<span style="color:red">' . $arr['error']['message'] . '</span>';
  }
  elseif(!empty($arr['choices'])){
	$return['message'] = trim($arr['choices'][0]['text']);
  }
  return json_encode($return);
}

if($_SERVER['REQUEST_METHOD'] == "POST"){
  $arr = json_decode(file_get_contents('php://input'), true);
  $message = $arr['message'];
  if(!empty($message)){
    $openai = openai($message);
    die($openai);
  }
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link
      rel="stylesheet"
      href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
      integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
      crossorigin="anonymous"
      />
    <title>СhatGPT</title>
  </head>
  <body>
    <div class="container">
      <h1 class="text-center">СhatGPT</h1>
      <div class="messages" id="messages">
        <!-- Messages will be displayed here -->
      </div>
      <form method="post">
        <div class="form-group">
          <input type="text" class="form-control" id="messageInput" placeholder="Enter a message" />
        </div>
        <button type="submit" class="btn btn-primary" id="sendMessage">
        Send
        </button>
      </form>
    </div>
  </body>
  <script>
    // Get the input field and submit button
    const messageInput = document.getElementById("messageInput");
    const sendMessage = document.getElementById("sendMessage");
    const messages = document.getElementById("messages");
    const loading = document.getElementById("loading");
    // Send message when submit button is clicked
    sendMessage.addEventListener("click", (event) => {
    event.preventDefault();
    
    // Get the message from the input field
    const message = messageInput.value;
    
	sendMessage.innerText = 'loading...';
	sendMessage.disabled = true;
	messageInput.disabled = true;
    
    // Create a new message element
    const messageElement = document.createElement("div");
    messageElement.innerHTML = '<p>You: ' + message + '</p>';
    messages.appendChild(messageElement);
    
    // Clear the input field
    messageInput.value = "";
    
    // Send the message to the server
    fetch("/", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ message: message }),
    })
    .then((response) => response.json())
    .then((data) => {
	  
	  sendMessage.innerText = 'Send';
      sendMessage.disabled = false;
	  messageInput.disabled = false;
	  messageInput.focus();
	  
      // Display the response from the server
      const messageElement = document.createElement("div");
	  const p = document.createElement("p");
      p.innerText = 'Server: ' + data.message;
      messageElement.appendChild(p);
      messages.appendChild(messageElement);
    });
    });
  </script>
</html>
