drop table if exists user;
create table user(
  id int primary key auto_increment,
  name varchar(200) default 2 not null comment "user name",
  age int,
  status tinyint,
  address_id int,
  unique key (name),
  unique key (status, address_id),
  key(age, status)
) DEFAULT charset=utf8 ENGINE=InnoDB;
