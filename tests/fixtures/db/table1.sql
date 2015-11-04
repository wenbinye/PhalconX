create table user(
id int primary key auto_increment,
name varchar(200) default 2 not null comment "foo name",
age int,
status tinyint,
address_id int,
unique key (name),
unique key (status, address_id),
key(age, status)
) default charset=utf8 engine=InnoDB;
