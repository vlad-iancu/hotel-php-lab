CREATE DATABASE IF NOT EXISTS hotel_db;
USE hotel_db;
DROP TABLE IF EXISTS REVIEW;
DROP TABLE IF EXISTS BOOKING;
DROP TABLE IF EXISTS ROOM;
DROP TABLE IF EXISTS WORKER_GROUP;
DROP TABLE IF EXISTS HOTEL;
DROP TABLE IF EXISTS TOKEN;
DROP TABLE IF EXISTS PERMISSION_GRANT;
DROP TABLE IF EXISTS PERMISSION;
DROP TABLE IF EXISTS USER;

CREATE TABLE USER (
  userId int NOT NULL AUTO_INCREMENT,
  userName varchar(64) DEFAULT NULL,
  email varchar(64) DEFAULT NULL,
  password varchar(128) DEFAULT NULL,
  PRIMARY KEY (`userId`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE PERMISSION(
	permissionId INT NOT NULL AUTO_INCREMENT,
    permissionName VARCHAR(64),
    PRIMARY KEY(permissionId)
);

CREATE TABLE PERMISSION_GRANT(
	permissionId INT,
    userId INT,
    PRIMARY KEY (permissionId, userId),
    
    CONSTRAINT PERMISSION_GRANT_TO_PERMISSION FOREIGN KEY (permissionId)
    REFERENCES PERMISSION(permissionId),
    
    CONSTRAINT PERMISSION_GRANT_TO_USER FOREIGN KEY (userId)
    REFERENCES USER(userId) ON DELETE CASCADE
);

CREATE TABLE TOKEN(
    value VARCHAR(64) PRIMARY KEY,
    tokenType INT,
    userId INT,
    expiration INT(11),
    
    CONSTRAINT TOKEN_TO_USER FOREIGN KEY (userId)
    REFERENCES USER(userId) ON DELETE CASCADE
);

CREATE TABLE HOTEL(
    hotelId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    hotelName VARCHAR(128),
    adminPermissionId INT,
    viewHotelPermissionId INT,
    createRoomPermissionId INT,
    
    CONSTRAINT CREATE_ROOM FOREIGN KEY (createRoomPermissionId)
    REFERENCES PERMISSION(permissionId),
    
    CONSTRAINT VIEW_HOTEL FOREIGN KEY (viewHotelPermissionId)
    REFERENCES PERMISSION(permissionId),
    
    CONSTRAINT HOTEL_ADMIN FOREIGN KEY (adminPermissionId)
    REFERENCES PERMISSION(permissionId) ON DELETE CASCADE,

    FULLTEXT KEY (hotelName)
);

CREATE TABLE WORKER_GROUP (
    hotelId INT,
    permissionId INT,
    PRIMARY KEY(hotelId, permissionId),

    CONSTRAINT WORKER_GROUP_TO_HOTEL FOREIGN KEY(hotelId)
    REFERENCES HOTEL(hotelId),

    CONSTRAINT WORKER_GROUP_TO_PERMISSION FOREIGN KEY(permissionId)
    REFERENCES PERMISSION(permissionId) ON DELETE CASCADE
);

DELIMITER $$
CREATE TRIGGER BEFORE_WORKER_GROUP_DELETE BEFORE DELETE
ON WORKER_GROUP FOR EACH ROW
BEGIN
    DELETE FROM PERMISSION WHERE PERMISSION.permissionId = OLD.permissionId;
END$$
DELIMITER ;

CREATE TABLE ROOM (
    roomId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    roomName VARCHAR(64),
    hotelId INT,
    writePermissionId INT,
    deletePermissionId INT,
    cancelBookingPermissionId INT,
    price INT,
    name VARCHAR(64),
    
    CONSTRAINT ROOM_HOTEL FOREIGN KEY (hotelId)
    REFERENCES HOTEL(hotelId) ON DELETE CASCADE,
    
    CONSTRAINT CANCEL_BOOKING FOREIGN KEY (cancelBookingPermissionId)
    REFERENCES PERMISSION(permissionId),
    
    CONSTRAINT ROOM_WRITE_PERMISSION FOREIGN KEY (writePermissionId)
    REFERENCES PERMISSION(permissionId),
    
    CONSTRAINT ROOM_DELETE_PERMISSION FOREIGN KEY (deletePermissionId)
    REFERENCES PERMISSION(permissionId),

    FULLTEXT KEY(roomName)
);

CREATE TABLE BOOKING (
    userId INT,
    roomId INT,
    date INT(11),
    
    CONSTRAINT BOOKING_TO_USER FOREIGN KEY (userId)
    REFERENCES USER(userId) ON DELETE CASCADE,
    
    CONSTRAINT BOOKING_TO_ROOM FOREIGN KEY (roomId)
    REFERENCES ROOM(roomId) ON DELETE CASCADE
);

CREATE TABLE REVIEW (
    userId INT,
    roomId INT,
    date INT(11),
    
    CONSTRAINT REVIEW_TO_USER FOREIGN KEY (userId)
    REFERENCES USER(userId) ON DELETE CASCADE,
    
    CONSTRAINT REVIEW_TO_ROOM FOREIGN KEY (roomId)
    REFERENCES HOTEL(hotelId) ON DELETE CASCADE	
);
INSERT INTO PERMISSION(permissionName) VALUES('ANONYMUS');
INSERT INTO PERMISSION(permissionName) VALUES('AUTHENTICATED');
INSERT INTO PERMISSION(permissionName) VALUES('APP_ADMIN');



GRANT ALL PRIVILEGES ON hotel_db.* TO 'hotel_db_user'@'localhost';
