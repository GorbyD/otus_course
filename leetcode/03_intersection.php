<?php

class Solution {

    /**
     * @param Integer[] $nums1
     * @param Integer[] $nums2
     * @return Integer[]
     */
    function intersection($nums1, $nums2) {
        $set1 = [];
        foreach ($nums1 as $num) {
            $set1[$num] = true;
        }

        $set2 = [];
        foreach ($nums2 as $num) {
            $set2[$num] = true;
        }

        $result = [];
        foreach ($set1 as $num => $flag) {
            if (isset($set2[$num])) {
                $result[] = $num;
            }
        }
        return $result;
    }
}

// Сложность
// Время: O(n)
// Память: O(n)