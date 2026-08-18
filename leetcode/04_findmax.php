<?php

class Solution {

    /**
     * @param Integer[] $nums
     * @return Integer
     */
    function findMaxK($nums) {
        $map = [];
        foreach ($nums as $num) {
            $map[$num] = true;
        }

        $max = -1;
        foreach ($nums as $num) {
            if ($num > 0 && isset($map[-$num])) {
                $max = max($max, $num);
            }
        }
        return $max;
    }
}

// Сложность
// Время: O(n)
// Память: O(n)