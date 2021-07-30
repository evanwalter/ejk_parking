CREATE DATABASE garage_project;

use garage_project;

DROP USER IF EXISTS 'garageuser'@'localhost';

CREATE USER 'garageuser'@'localhost' IDENTIFIED WITH mysql_native_password BY 'etA36wq51';

GRANT ALL privileges ON garage_project.* TO 'garageuser'@'localhost'; 


FLUSH PRIVILEGES;
DROP TABLE IF EXISTS Payment;
DROP TABLE IF EXISTS PaymentMethod;
DROP TABLE IF EXISTS PaymentMethodType;
DROP TABLE IF EXISTS ParkingEvent;
DROP TABLE IF EXISTS Vehicle;
DROP TABLE IF EXISTS Customer;
DROP TABLE IF EXISTS Camera;
DROP TABLE IF EXISTS FeeMatrix;

CREATE TABLE Customer(
	customer_id integer NOT NULL AUTO_INCREMENT primary key,
	is_employee bool not null default false,
    is_admin bool not null default false,
    is_fee_exempt bool not null default false,
    prohibit_from_entering bool not null default false, -- delinquent on payment
    first_name nvarchar(100) NULL,
    last_name nvarchar(100) NULL,
    email nvarchar(100) NULL unique,
    street_address nvarchar(100) NULL,
    postal_code varchar(25) NULL,
    state_code varchar(10) NULL,
    country varchar(25) NULL,
    online_username varchar(50) NULL,
    online_password varchar(50) NULL);
    
CREATE TABLE VEHICLE(
	vehicle_id integer NOT NULL AUTO_INCREMENT primary key,
	licence_number varchar(50) NOT NULL,
    state varchar(25) NOT NULL,
    customer_id integer NULL,
    unique(licence_number,state),
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
	);
    
    
CREATE TABLE Camera(
	camera_id integer NOT NULL AUTO_INCREMENT primary key,
    location varchar(100) NULL,
    authentication_key varchar(100) 
);

DROP TABLE IF EXISTS ParkingEvent;
CREATE TABLE ParkingEvent(
	parking_event_id integer NOT NULL AUTO_INCREMENT primary key,
	vehicle_id integer NOT NULL,
	time_start datetime NOT NULL,
    time_end datetime NULL,
    fee_amount numeric(10,2) NULL,
    fee_exempt bool default false ,
    payment_id integer NULL,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicle(vehicle_id) ON DELETE CASCADE
);
    
CREATE TABLE FeeMatrix(
	mintime numeric(10,2),
    maxtime numeric(10,2),
    fee     numeric(10,2)
);

/*        RECORD THE PAYMENT TYPES AVAILABLE ****/ 
DROP TABLE IF EXISTS PaymentMethodType;
CREATE TABLE PaymentMethodType(
	payment_method_type_id integer NOT NULL AUTO_INCREMENT primary key,
    payment_type_name varchar(25) UNIQUE,
    payment_type_description varchar(100) NULL
);
INSERT INTO PaymentMethodType(payment_type_name) VALUES('Visa');
INSERT INTO PaymentMethodType(payment_type_name) VALUES('MC');
INSERT INTO PaymentMethodType(payment_type_name) VALUES('AmExpress');
INSERT INTO PaymentMethodType(payment_type_name) VALUES('Paypal');
INSERT INTO PaymentMethodType(payment_type_name) VALUES('Venmo');

--  SELECT * from PaymentMethodType

DROP TABLE IF EXISTS PaymentMethod;
CREATE TABLE PaymentMethod(
	payment_method_id integer NOT NULL AUTO_INCREMENT primary key,
    customer_id integer NULL,
    payment_method_type_id integer,
    payment_method_alias varchar(100) NULL,
    card_number varchar(100) NULL,
    card_month char(2) NULL,
    card_year char(2) NULL,
    account_id varchar(50) NULL,     -- FOR ONLINE PAYMENT SETS
    account_password varchar(100) NULL,
	auto_pay boolean default false,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_type_id) REFERENCES PaymentMethodType(payment_method_type_id) ON DELETE CASCADE
);

/*        RECORD THE ACTUAL PAYMENT INCIDENT ****/ 
DROP TABLE IF EXISTS Payment;
CREATE TABLE Payment(
	payment_id integer NOT NULL AUTO_INCREMENT primary key,
    parking_event_id integer,
    payment_method_id integer,
    payment_date datetime,
    accepted boolean,
    confirmation_number varchar(100) NULL,
    FOREIGN KEY (parking_event_id) REFERENCES ParkingEvent(parking_event_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES PaymentMethod(payment_method_id) ON DELETE CASCADE
);
  
  Select * from FeeMatrix
/************** LOADING TEST DATA ******************/    

INSERT INTO CUSTOMER(online_username,online_password,is_employee,is_admin) 
	VALUES ('jaylin','jaylin',true,true);  
INSERT INTO CUSTOMER(online_username,online_password,is_employee,is_admin) 
	VALUES ('kaitlin','kaitlin',true,true);  
INSERT INTO CUSTOMER(online_username,online_password,is_employee,is_admin) 
	VALUES ('evan','evan',true,true);
--  Test generic user    
INSERT INTO CUSTOMER(is_employee,is_admin) 
	VALUES (false,false);  

-- select * from CUSTOMER;
INSERT INTO Vehicle(customer_id,licence_number,state) VALUES (4,'63fdt','TX');  
 
-- select * from VEHICLE;

INSERT INTO Camera(location,authentication_key) VALUES ('Main entry gate','tnlkc6nfe363atedae94h');
INSERT INTO Camera(location,authentication_key) VALUES ('Main exit gate','tnlkc6nfe363atedae94h');

INSERT INTO FeeMatrix VALUES(0,1.00,1.00);
INSERT INTO FeeMatrix VALUES(1.01,2.00,2.00);
INSERT INTO FeeMatrix VALUES(2.01,4.00,3.00);
INSERT INTO FeeMatrix VALUES(4.01,8.00,5.00);
INSERT INTO FeeMatrix VALUES(8.01,24.00,10.00);
INSERT INTO FeeMatrix VALUES(24.01,99999.00,20.00);

-- SELECT * FROM FeeMatrix
/*

Select * from Camera;

SELECT * from ParkingEvent;
SELECT * FROM Customer;
SELECT * from Vehicle

update paymentmethod set customer_id=1 where payment_method_id=1
update Vehicle set customer_id=1 where vehicle_id=24
 

select * 
from paymentmethod;


*/

