<?php

class Solution {

    /**
     * @param Integer $numerator
     * @param Integer $denominator
     * @return String
     */
    function fractionToDecimal($numerator, $denominator) {
        if ($numerator === 0) {
            return "0";
        }

        $sign = "";
        if ($numerator < 0 && $denominator < 0) {
        } elseif ($numerator < 0 || $denominator < 0) {
            $sign = "-";
        }

        $numerator = abs($numerator);
        $denominator = abs($denominator);

        $intPart = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        if ($remainder === 0) {
            return $sign . $intPart;
        }


        // если остаток встретился повторно - дальше зациклится, определен период
        $fraction = "";
        $seenAt = []; // остаток => позиция в $fraction, на которой он встретился

        while ($remainder !== 0) {
            if (isset($seenAt[$remainder])) {
                $pos = $seenAt[$remainder];
                $fraction = substr($fraction, 0, $pos) . "(" . substr($fraction, $pos) . ")";
                break;
            }

            $seenAt[$remainder] = strlen($fraction);

            $remainder *= 10;
            $fraction .= intdiv($remainder, $denominator);
            $remainder %= $denominator;
        }

        return $sign . $intPart . "." . $fraction;
    }
}

// Сложность
// Время: O(denominator) - остаток принимает не более denominator разных значений
// Память: O(denominator)
