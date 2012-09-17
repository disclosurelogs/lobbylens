<?php
spl_autoload_register(function ($className){
    $path = __DIR__ . '/' . str_replace('\\', '/', $className) . '.php';

    if (file_exists($path))
    {
      include($path);
    }
  }
);

$driver = new Orient\Http\Client\Curl();
$orient = new Orient\Foundation\Binding($driver, '127.0.0.1', '2480', 'admin', 'admin', 'demo');
$q2 = "insert into fellas (name) values ('Michelle Obama'); -- our starting point
insert into fellas (name) values ('Barack Obama'); -- Michelle's husband ( =  friend )
insert into fellas (name) values ('Angela Merkel'); -- Barack's friend
insert into fellas (name) values ('Nicolas Sarkozy'); -- Angela's friend
insert into fellas (name) values ('Silvio Berlusconi'); -- friend of no one
update fellas add friends = [Barack @rid] where @rid = [Michelle @rid]
update fellas add friends = [Michelle @rid] where @rid = [Barack @rid]
update fellas add friends = [Barack @rid] where @rid = [Angela @rid]
update fellas add friends = [Angela @rid] where @rid = [Barack @rid]
update fellas add friends = [Nicolas @rid] where @rid = [Angela @rid]
update fellas add friends = [Angela @rid] where @rid = [Nicolas @rid]";
$response = $orient->query("select from fellas where any() traverse(0,-1) ( @rid = [Michelle @rid] ) and @rid <> [Michelle @rid]");
$output = json_decode($response->getBody());
foreach ($output->result as $friend)
{
  var_dump($friend->name);
}
        ?>