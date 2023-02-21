<?php
/*
 https://github.com/alexz006/ChatGPT-Example
  
 Get OPENAI_API_KEY
 here (accessToken): https://chat.openai.com/api/auth/session
 or here (API key): https://platform.openai.com/account/api-keys
*/
define('OPENAI_API_KEY', '');

set_time_limit(120);

function openai_api($message){
  
  $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';
  $headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY
  ];
  $data = [
    "prompt" => $message,
    'max_tokens' => 4000,
    "temperature" => 0.9,
  ];
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response  = curl_exec($ch);
  if (curl_errno($ch)) {
    $return = [
      'error' => ['msg'=>'<span style="color:red">' . curl_error($ch) . '</span>']
    ];
    curl_close($ch);
    return $return;
  }
  curl_close($ch);
  $arr = json_decode($response, 1);

  $return = [
    'message' => ''
  ];
  if(!empty($arr['error'])){
    $return['error'] = ['msg'=>'<span style="color:red">' . $arr['error']['message'] . '</span>'];
  }
  elseif(!empty($arr['choices'])){
    $return['message'] = trim($arr['choices'][0]['text']);
  }
  return $return;
}

if($_SERVER['REQUEST_METHOD'] == "POST"){
  $arr = json_decode(file_get_contents('php://input'), true);
  if(!empty($arr['message'])){
  header('Content-Type: application/json');
    $openai = openai_api($arr['message']);
  // highlight php code 
  $openai["message"] = replace_html(trim($openai["message"]));
    die(json_encode($openai));
  }
}

/*--------------------*/

// replace_html
function replace_html($string) {
  
  // find_markdown
  preg_match_all('/(?:```|`)(.*?)(?:```|`)/s', $string, $find_markdown);
  foreach($find_markdown[1] as $i=>$find)
    $string = preg_replace('~'.preg_quote($find).'~', "-find_markdown{$i}-", $string, 1);
  
  if(empty($find_markdown[1])){
    
    // find_php
    preg_match_all('/(<\?(?:[^\s\n]*)?[\s\n]+.*?\?>)/s', $string, $find_php);
    foreach($find_php[1] as $i=>$find)
      $string = preg_replace('~'.preg_quote($find).'~', "-find_php{$i}-", $string, 1);
      
    // htmlspecialchars other code
    $string = htmlspecialchars($string);
    $string = str_replace("\n", '<br>', $string);
    
    // return find_php and highlight_string
    preg_match_all('/(-find_php[0-9]+?-)/s', $string, $m);
    foreach($m[1] as $i=>$find){
      $find_php[1][$i] = highlight_string($find_php[1][$i], true);
      $string = preg_replace('~'.preg_quote($find).'~', $find_php[1][$i], $string, 1);
    }
    
  }
  else {
    
    // return find_markdown
    preg_match_all('~(?:```|`)(-find_markdown[0-9]+?-)(?:```|`)~s', $string, $m);
    foreach($m[1] as $i=>$find)
      $string = preg_replace('~'.preg_quote($find).'~', $find_markdown[1][$i], $string, 1);
      
  }
  return $string;
}
