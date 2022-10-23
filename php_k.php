<?php
function quickSort(array &$a, int $start = 0, int $last = null) {
    $wall = $start;
    $last = is_null($last) ? count($a) - 1 : $last;
    if ($last - $start < 1)
      return;
  
    switchValues($a, (int) (($start + $last) / 2), $last);
    for ($i = $start; $i < $last; $i++)
      if ($a[$i] < $a[$last]) {
        switchValues($a, $i, $wall);
        $wall++;
      }
  
    switchValues($a, $wall, $last);
    quickSort($a, $start, $wall - 1);
    quickSort($a, $wall + 1, $last);
  }
  
  function switchValues(array &$a, int $i1, int $i2) {
    if ($i1 !== $i2) {
      $temp = $a[$i1];
      $a[$i1] = $a[$i2];
      $a[$i2] = $temp;
    }
  }
  
  function generateArr(int $size): array {
    $arr = [];
    for ($i = 0; $i < $size; $i++) {
      $arr[] = (int) (rand() / (1000000000 / $size));
    }
    return $arr;
  }
  
  function run(){
    $size = 1000000;
  $arr = generateArr($size);
  $start = microtime(true);
  quickSort($arr);
  
  $duration = round((microtime(true) - $start) * 1000 * 1000) / 1000;
  echo "Sorted $size elements in {$duration}ms\n";
  
  }
 

  run();

