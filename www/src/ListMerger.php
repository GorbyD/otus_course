<?php

class ListMerger
{
    public function mergeTwoLists(?ListNode $list1, ?ListNode $list2): ?ListNode
    {
        if ($list1 === null) {
            return $list2;
        }
        if ($list2 === null) {
            return $list1;
        }

        if ($list1->val <= $list2->val) {
            $head = $list1;
            $list1 = $list1->next;
        } else {
            $head = $list2;
            $list2 = $list2->next;
        }

        $tail = $head;

        while ($list1 !== null && $list2 !== null) {
            if ($list1->val <= $list2->val) {
                $tail->next = $list1;
                $list1 = $list1->next;
            } else {
                $tail->next = $list2;
                $list2 = $list2->next;
            }
            $tail = $tail->next;
        }

        $tail->next = $list1 ?? $list2;

        return $head;
    }

    public function fromArray(array $values): ?ListNode
    {
        if ($values === []) {
            return null;
        }

        $head = new ListNode((int) array_shift($values));
        $tail = $head;

        foreach ($values as $value) {
            $tail->next = new ListNode((int) $value);
            $tail = $tail->next;
        }

        return $head;
    }

    public function toArray(?ListNode $head): array
    {
        $values = [];

        while ($head !== null) {
            $values[] = $head->val;
            $head = $head->next;
        }

        return $values;
    }
}
