<?php
return [
    'user' => array (
        'columns' => array (
            'id' => 'integer(11)=int isNumeric notNull autoIncrement first',
            'name' => 'varchar(200) notNull after=id {"default":"2","comment":"foo name"}',
            'age' => 'integer(11)=int isNumeric after=name',
            'status' => 'integer(4)=int isNumeric after=age',
            'address_id' => 'integer(11)=int isNumeric after=status',
        ),
        'indexes' => array (
            'PRIMARY' => 'PRIMARY KEY(id)',
            'name' => 'UNIQUE KEY(name)',
            'status' => 'UNIQUE KEY(status,address_id)',
            'age' => 'KEY(age,status)',
        ),
        'options' => 'table_type=BASE TABLE,auto_increment=1,engine=InnoDB,table_collation=utf8_general_ci',
    )
];
