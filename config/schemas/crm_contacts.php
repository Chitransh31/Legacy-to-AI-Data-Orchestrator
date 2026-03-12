<?php

declare(strict_types=1);

return [
    'table' => 'contacts',
    'mappings' => [
        'CNTCT_ID' => 'contact_id',
        'FIRST_NM' => 'first_name',
        'LAST_NM' => 'last_name',
        'EMAIL_ADDR' => 'email',
        'PHONE_NUM' => 'phone',
        'COMPANY_NM' => 'company',
        'CNTCT_DT' => 'contact_date',
        'NOTES_TXT' => 'notes',
        'STATUS_CD' => 'status',
    ],
    'required' => ['CNTCT_ID', 'FIRST_NM', 'LAST_NM'],
    'types' => [
        'CNTCT_ID' => 'string',
        'CNTCT_DT' => 'date',
    ],
    'exclude' => ['SYS_CR_DT', 'SYS_UPD_DT', 'USR_ID'],
    'max_lengths' => [
        'NOTES_TXT' => 300,
    ],
    'importance' => ['contact_id', 'first_name', 'last_name', 'email', 'company', 'status'],
];
