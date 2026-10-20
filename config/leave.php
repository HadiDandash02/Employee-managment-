<?php

/*
|--------------------------------------------------------------------------
| Leave allowance
|--------------------------------------------------------------------------
|
| Every employee starts each calendar year with "base_days" (HR can change it
| per employee on the employee form) and earns "monthly_accrual" days at the
| start of every month: in October that is 3 + 10 × 1.25 = 15.5 days.
| Someone hired this year earns from their hire month. See LeaveBalanceService.
|
*/

return [

    'base_days' => 3,

    'monthly_accrual' => 1.25,

];
