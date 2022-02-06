CREATE TABLE user (
    id INT AUTO_INCREMENT,
    usertype VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE = INNODB;
CREATE TABLE car (
    id INT AUTO_INCREMENT,
    agency_id INT,
    model VARCHAR(255) NOT NULL,
    num VARCHAR(255) NOT NULL,
    seat_cap INT NOT NULL,
    rent_per_day INT NOT NULL,
    is_available TINYINT(1) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (agency_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE = INNODB;
CREATE TABLE rent (
    id INT AUTO_INCREMENT,
    car_id INT,
    booked_by_id INT NOT NULL,
    start_date DATE NOT NULL,
    num_of_days INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (car_id) REFERENCES car(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE = INNODB;