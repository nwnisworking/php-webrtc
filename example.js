
while(1){
  $i++;
  $i%= count($arr);

  var_dump($i.": ".$arr[$i]);

  if($i == 1){
    array_splice($arr, $i, 1);
    $i--;
    continue;
  }

  sleep(1);
}