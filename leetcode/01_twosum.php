<?php

class Solution {

    /**
     * @param Integer[] $nums
     * @param Integer $target
     * @return Integer[]
     */
    function twoSum($nums, $target) {
        $has = [];
        foreach ($nums as $k => $num) {
            $needNum = $target - $num;
            if (isset($has[$needNum])) {
                return [$has[$needNum], $k];
            }
            $has[$num] = $k;
        }
        return [];
    }
}

// Сложность
// Время: O(n)
// Память: O(n)