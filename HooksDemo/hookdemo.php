<?php

// Our Example Chat Conversation
$messages=<<<EOF
Billy: Hello
Bob: Hi
Trinity: Ready To play?
Bob: Yep!
Trinity: !Trivia
Bob: !Trivia
Billy: !Trivia
Trinity: 42
Trinity: Marvin
Bob: 42
Trinity: Douglas Adams
Bob: Marvin
Bob: Douglas Adams
Billy: 42
Billy: Marvin
Billy: Douglas Adams
Billy: You guys are too fast at this!
Trinity: AGAIN!
Bully: Can I Watch
Trinity: Well Ok sit and watch
Trinity: !Trivia
Bob: !Trivia
Billy: Geeze can barely type 2 words before u get a line out
Trinity: 42
Trinity: Marvin
Bob: 42
Trinity: Douglas Adams
Bob: Marvin
Billy: I think I need to learn how to type better
Bob: Douglas Adams
Trinity: Time for u to die Bot!
Trinity: !Quit
EOF;

// Split our conversation into individual lines
$lines=explode("\n",$messages);

// Our Trivia
$trivia=array(
  array('The answer to Life, the Univers and Everything','42'),
  array('The name of the robot in Hitchhiker\'s Guide','Marvin'),
  array('Who wrote HitchHiker\'s Guide','Douglas Adams'),
  );

// This holds various information for our functions
$data=array(
  'line'=>NULL,  // Current recieved line
  'trivia'=>array(),  // Used by trivia module to store info
  'msghooks'=>array(),  // For the Public Message Controller
  'timer_events'=>array(),  // For the Timer Event Controller
  );

// Add A Timer Event - This is to emulate the recieving of messages
event_timer_add(1,1,'server_line');
// Yes we are connected
$connected=TRUE;
// How long shall we run while connected (300 = 5 minutes)
$lt=time()+300;

// Our Controller
while($connected && (time()<=$lt))
{
  event_timer_check();
  process_line();
}
// Well we got disconnected somehow
die('disconnected');

// Process a line from the recieve messages
function process_line()
{
  $line=&$GLOBALS['data']['line'];  // Get our line
  if($line==NULL) return;  // Nothing there yer, go back
  list($user,$msg)=explode(':',$line,2);  // Seperate User from the rest of the message
  $msg=trim($msg);  // remove any leading/trailing spaces
  echo date('H:i:s')." $user> $msg".PHP_EOL; // Format and display
  if($msg[0]=='!')  // This is our bot command character
  {
    $msg=substr($msg,1);  // remove the command character
    if(strpos($msg,' ')!==FALSE) // do we have a message attached to the bot code
    {
      list($code,$msg)=explode(' ',$msg,2); // Yes, Seperate the code from the message
    } else {
      $code=$msg;  // No, code is our message
      $msg='';  // Blank out the message
    }
    if(function_exists(strtolower($code).'_msg'))  // do we have a function tied to this code?
    {
      call_user_func(strtolower($code).'_msg',$user); // Yes, Well call the function than
    }
  } elseif(count($GLOBALS['data']['msghooks'])) { // Do we Have any Message Hooks?
    message_hooks($user,$msg); // Yes, well process the message hooks
  }
  $line=NULL; // The line was processed, so remove it from our buffer
}

// Public Message hook handler
function message_hooks($user,$msg)
{
  $hooks=&$GLOBALS['data']['msghooks'];

  foreach($hooks as $hook) // Process each message hook
  {
    if($hook[1]==NULL || $hook[1]==$user)  // Do we have a message hook for this user (NULL=All Users)?
    {
        call_user_func_array($hook[0],array($user,$msg)); // Yes, Well process the user message
    }
  }
  
}

// Add A Message Hook
function msg_hook_add($function,$user=NULL)
{
  $hooks=&$GLOBALS['data']['msghooks'];
  $hooks[]=array($function,$user);
}

// Remove a message hook
function msg_hook_del($function,$user=NULL)
{
  $hooks=&$GLOBALS['data']['msghooks'];
  foreach($hooks as $key=>$hook)
  {
    if($hook[0]=$function && $hook[1]=$user)
      unset($hooks[$key]);
  }
}

// Add A timer Event
function event_timer_add($time=0,$repeat=FALSE,$function='echo',$param=NULL)
{
  $tevents=&$GLOBALS['data']['timer_events'];
  
  if($time>0)
    $tevents[]=array($time,$repeat,$function,$param,time()+$time);
}

// Check our timers
function event_timer_check()
{
  $tevents=&$GLOBALS['data']['timer_events'];
  
  // Do we have timer events?
  if(!count($tevents))
    return; // Nope, go back
  
  // Process each timer event
  foreach($tevents as $key=>&$event)
  {
    if($event[4]<=time()) // Has the timer expired?
    {
      call_user_func($event[2],$event[3]);  // Yep, call the timer function
      if(!$event[1])  // Is this a non-repeating timer?
        unset($tevents[$key]); // yes, Remove from event list
      else
        $event[4]=time()+$event[0]; // No, Update our next time for event
    }
  }
}

// This fakes the chat log in the beginning
function server_line()
{
  $line=&$GLOBALS['data']['line'];
  $data=&$GLOBALS['lines'];
  
  if($line!==NULL)  // Is there something in the recieve buffer already?
    return; // Yes, Go Back
  $line=array_shift($data); // Nope, toss in the next line
}

// Bot Quit Code
function quit_msg($user)
{
  $lines=&$GLOBALS['lines'];
  event_timer_add(2,0,'quit_event'); // Add a timer event, give 2 seconds to disconnect
  array_unshift($lines,"BOT: $user told me to quit :("); // tell everyone that user was a bad person
}

// our quit event
function quit_event()
{
  $GLOBALS['connected']=FALSE; // Tell our controller we got disconnected
}

// Bot Trivia Code
function trivia_msg($user)
{
  $trivia=$GLOBALS['trivia'];
  $trivspots=&$GLOBALS['data']['trivia'];
  $lines=&$GLOBALS['lines'];
  
  $triv=rand(1,count($trivia))-1; // Pick a random question.
  $newspot=count($trivspots); // Whats the next array element in our trivia spots
  $trivspots[$newspot]=array($user,$triv); // Add a new triviaspot
  
  array_unshift($lines,"BOT: $user Here is your question: ". $trivia[$triv][0]); // Put this in our chat buffer
  event_timer_add(10,0,'trivia_timeout',$triv); // Add an event, how long user has to respond
  msg_hook_add('trivia_check',$user);  // Add a message event, from this user
}

// Oooo User failed to respond in time
function trivia_timeout($id)
{
  $trivspots=&$GLOBALS['data']['trivia'];
  $lines=&$GLOBALS['lines'];
  if(isset($trivspots[$id])) // Do we have a valid trivspot
  {
    $ts=$trivspots[$id];  // Yes
    array_unshift($lines,"BOT: Ohhh tooo bad $ts[0] U ran out of time"); // Tell user he is a dumbass
    msg_hook_del('trivia_check',$trivspots[$id][0]); // remove the message hook
    unset($trivspots[$id]); // remove the trivspot
  }
}

// User posted sumfin in channel
function trivia_check($user,$msg)
{
  $lines=&$GLOBALS['lines'];
  $trivspots=&$GLOBALS['data']['trivia'];
  $trivia=$GLOBALS['trivia'];
  $ok=FALSE;
  // check all trivspots
  foreach($trivspots as $key=>$triv)
  {
    if($triv[0]==$user)  // Is User in trivspot?
    {
      $tk=$triv[1]; // Yes, Get which trivia he was linked to
      $msg=substr(trim(strtolower($msg)),0,strlen($trivia[$tk][1])); // normalize the answer and compare it against the user
      if($msg==strtolower(trim($trivia[$tk][1]))) // Did user answer correctly
      {
        $msg="Correct!";  // Yep
        msg_hook_del('trivia_check',$user); // remove the message hook for this user
        unset($trivspots[$key]); // remove the trivspot
      } else {
        $msg= "Incorrect :("; // Sucker, try again
      }
      array_unshift($lines,"BOT: $user that is $msg");; // Add a message to our chat
    }
  }
}
