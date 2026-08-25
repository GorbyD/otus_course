<?php

class Solution {

    /**
     * @param Integer[] $nums
     * @return Integer[]
     */
    function smallerNumbersThanCurrent($nums) {
        $sorted = $nums;
        sort($sorted);

        // индекс первого вхождения числа - количество элементов, которые меньше него
        $map = [];
        foreach ($sorted as $i => $num) {
            if (!isset($map[$num])) {
                $map[$num] = $i;
            }
        }

        foreach ($nums as $num) {
            $result[] = $map[$num];
        }

        return $result ?? [];
    }
}

// Сложность
// Время: O(n log n), т.к. sort
// Память: O(n)