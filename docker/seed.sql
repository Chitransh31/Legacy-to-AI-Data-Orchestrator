-- Legacy ERP sample data for development
CREATE TABLE IF NOT EXISTS orders (
    ORD_ID VARCHAR(20) PRIMARY KEY,
    CUST_NM VARCHAR(100) NOT NULL,
    CUST_EMAIL VARCHAR(150),
    ORD_DT VARCHAR(20) NOT NULL,
    SHIP_DT VARCHAR(20),
    ORD_TOT DECIMAL(12,2) NOT NULL,
    ORD_STATUS VARCHAR(20),
    PROD_DESC TEXT,
    QTY INT,
    UNIT_PRC DECIMAL(10,2),
    INTERNAL_ID INT AUTO_INCREMENT UNIQUE,
    AUDIT_TS TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFIED_BY VARCHAR(50) DEFAULT 'SYSTEM'
);

CREATE TABLE IF NOT EXISTS contacts (
    CNTCT_ID VARCHAR(20) PRIMARY KEY,
    FIRST_NM VARCHAR(50) NOT NULL,
    LAST_NM VARCHAR(50) NOT NULL,
    EMAIL_ADDR VARCHAR(150),
    PHONE_NUM VARCHAR(30),
    COMPANY_NM VARCHAR(100),
    CNTCT_DT VARCHAR(20),
    NOTES_TXT TEXT,
    STATUS_CD VARCHAR(10),
    SYS_CR_DT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    SYS_UPD_DT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    USR_ID VARCHAR(30) DEFAULT 'ADMIN'
);

-- Sample orders
INSERT INTO orders (ORD_ID, CUST_NM, CUST_EMAIL, ORD_DT, SHIP_DT, ORD_TOT, ORD_STATUS, PROD_DESC, QTY, UNIT_PRC) VALUES
('ORD-001', 'Acme Corporation', 'orders@acme.com', '01/15/2024', '01/18/2024', 15750.00, 'SHIPPED', 'Industrial Widget Assembly Kit - Premium Grade with extended warranty and maintenance package included for 24 months', 50, 315.00),
('ORD-002', 'TechStart Inc', 'procurement@techstart.io', '02/20/2024', NULL, 8400.00, 'PENDING', 'Cloud Server License Bundle - Enterprise Tier', 12, 700.00),
('ORD-003', 'Global Logistics  LLC', 'buying@globallogistics.com', '03/10/2024', '03/12/2024', 22500.00, 'DELIVERED', 'Fleet Tracking Sensor Pack with GPS and real-time telemetry capabilities for commercial vehicles', 100, 225.00),
('ORD-004', 'Smith & Associates', NULL, '2024/04/05', NULL, 3200.00, 'CANCELLED', 'Office Furniture Set - Standing Desk Bundle', 4, 800.00),
('ORD-005', 'DataFlow Systems', 'orders@dataflow.co', '05-15-2024', '05-20-2024', 45000.00, 'SHIPPED', 'Data Center Rack Mount Server with redundant power supply and hot-swappable drive bays for enterprise deployment', 5, 9000.00);

-- Sample contacts
INSERT INTO contacts (CNTCT_ID, FIRST_NM, LAST_NM, EMAIL_ADDR, PHONE_NUM, COMPANY_NM, CNTCT_DT, NOTES_TXT, STATUS_CD) VALUES
('CNT-001', 'John', 'Smith', 'john.smith@acme.com', '555-0101', 'Acme Corporation', '01/10/2024', 'Key account manager. Decision maker for large orders.  Prefers email communication.', 'ACTIVE'),
('CNT-002', 'Sarah', 'Chen', 'sarah.chen@techstart.io', '555-0102', 'TechStart Inc', '02/15/2024', 'CTO. Very technical, needs detailed specifications before purchasing.', 'ACTIVE'),
('CNT-003', 'Mike', 'Johnson', NULL, '555-0103', 'Global Logistics LLC', '03/01/2024', NULL, 'INACTIVE'),
('CNT-004', 'Emily', 'Davis', 'emily@smithassoc.com', NULL, 'Smith & Associates', '04/01/2024', 'New contact. First meeting scheduled for next week.', 'PROSPECT');
