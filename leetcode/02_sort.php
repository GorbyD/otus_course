<?php

class Solution {

    /**
     * @param Integer[] $nums
     * @return Integer[]
     */
    function frequencySort($nums) {
        $count = [];
        foreach ($nums as $num) {
            if (!isset($count[$num])) {
                $count[$num] = 0;
            }
            $count[$num]++;
        }

        usort($nums, function ($a, $b) use ($count) {
            if ($count[$a] === $count[$b]) {
                return $b - $a;
            }
            return $count[$a] - $count[$b];
        });

        return $nums;
    }
}

// Сложность
// Время: O(n log n), т.к. для usort n элементов - O(n log n)
// Память: O(n)