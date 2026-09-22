<?php

class ListNode {
    public $val = 0;
    public $next = null;
    function __construct($val = 0, $next = null) {
        $this->val = $val;
        $this->next = $next;
    }
}



class Solution {

    /**
     * @param ListNode $headA
     * @param ListNode $headB
     * @return ListNode
     */
    function getIntersectionNode($headA, $headB) {
        // длина A до пересечения = a
        // длина B до пересечения = b
        // общий хвост = c
        // Вся длина A = a+c
        // Вся длина B = b+c
        // Если есть пересечение:
        //$pointerA пройдет a + c +b
        //$pointerB пройдет b + c + a
        //оба поинтера попадут на общую часть через одну длину
        //если пересечения нет - оба пройдут a+b и упадут на null одновременно

        $pointerA = $headA;
        $pointerB = $headB;

        while ($pointerA !== $pointerB) {
            if (is_null($pointerA)) {
                $pointerA = $headB;
            } else {
                $pointerA = $pointerA->next;
            }
            if (is_null($pointerB)) {
                $pointerB = $headA;
            } else {
                $pointerB = $pointerB->next;
            }
        }

        return $pointerA;
    }
}

// Сложность
// Время: O(m + n)
// Память: O(1)
