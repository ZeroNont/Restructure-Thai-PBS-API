<?php

return [
    'page_limit' => 'integer|in:5,10,25,50,100',
    'primary' => 'integer|min:1',
    'page' => 'integer|min:1',
    'keyword' => 'string|between:1,250',
    'permission' => [
        'menu_code' => 'in:meeting,proposal,dashboard,member,permission',
        'func_code' => 'in:access,create,update,delete,search,approve,view'
    ],
    'user' => [
        'username' => 'string|between:5,30',
        'password' => 'string|between:1,30',
        'contract_password' => 'regex:/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])([^\s]){8,128}$/s',
        'email' => 'email|regex:/[a-zA-Z0-9._@-]{10,50}/s',
        'mobile_phone' => 'regex:/^[0]{1}[689]{1}[0-9]{8}$/s',
        'policy_version' => 'regex:/^[\d]{1,3}.[\d]{1,3}.[\d]{1,3}$/s',
        'employee_code' => 'regex:/^[A-Z]{2}[0-9]{4}$/s',
        'actor_code' => 'in:ADMIN,SECRET,LEADER,MEMBER',
        'status_code' => 'in:ACTIVE,INACTIVE,PENDING'
    ],
    'text' => [
        'name' => 'string|between:1,100',
        'note' => 'string|between:1,250',
        'paper' => 'string|min:1',
        'title' => 'string|between:1,100'
    ],
    'meeting' => [
        'meeting_code' => 'string|between:1,20',
        'type_code' => 'in:CONSIDER,NOTICE,CONT',
        'status_code' => 'in:CREATED,PROGRESS,DONE,CANCELED',
        'priority_level' => 'in:LOWEST,LOW,MEDIUM,HIGH,HIGHEST,CRITICAL',
        'resolution_code' => 'in:BOC,BOM',
        'topic_no' => 'regex:/^[0-9]{1,2}([.][0-9]{1,2})*$/s',
        'join_code' => 'in:JOINED,REJECTED',
        'email' => [
            'edit_code' => 'in:DATE,TIME,ADDRESS,URL,SUBJECT',
            'mode_code' => 'in:NEW,EDIT,CANCEL'
        ],
        'reference' => 'regex:/^[0-9a-zA-Z]{256}$/s'
    ],
    'proposal' => [
        'type_code' => 'in:CONS,NOTICE,CONT',
        'level_code' => 'in:D,V,B'
    ],
    'number' => [
        'pin' => 'digits:6',
        'no' => 'integer|between:1,1000'
    ],
    'date' => [
        'started_at' => 'date|date_format:Y-m-d H:i:s',
        'ended_at' => 'date|date_format:Y-m-d H:i:s|after:started_at',
        'day' => 'date_format:d',
        'month' => 'date_format:m',
        'year' => 'numeric|between:2000,2600'
    ],
    'utilities' => [
        'attached_file' => 'mimes:pdf,jpg,jpeg,png,doc,docx|max:8192',
        'attached_img' => 'mimes:jpg,jpeg,png|max:4096',
        'ext_file' => 'in:pdf,jpg,jpeg,png,doc,docx',
        'module_code' => 'in:PROP,TOPIC,AGENDA,PROFILE'
    ],
    'template' => [
        'type_code' => 'in:P,T'
    ],
    'filter' => [
        'calendar' => [
            'join_code' => 'in:JOINED,REJECTED,WAIT',
            'status_code' => 'in:WAIT,DONE,CANCELED',
            'is_publish' => 'in:ON,OFF',
            'is_secreted' => 'in:ON,OFF'
        ]
    ]
];