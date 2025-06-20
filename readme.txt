# โครงสร้างไฟล์ระบบเรียกคิวรับยา

```
pharmacy_queue/
├── index.php                    # หน้าหลัก - สร้างคิวใหม่
├── display.php                  # จอแสดงคิว
├── login.php                    # หน้าล็อกอิน
├── logout.php                   # ล็อกเอาต์
├── 
├── config/
│   └── database.php            # การตั้งค่าฐานข้อมูล
├── 
├── includes/
│   ├── auth.php                # ระบบการยืนยันตัวตน
│   ├── queue_manager.php       # จัดการคิว
│   ├── header.php              # ส่วนหัวเว็บไซต์
│   └── footer.php              # ส่วนท้ายเว็บไซต์
├── 
├── admin/
│   ├── dashboard.php           # แดชบอร์ดผู้ดูแล
│   ├── manage_queue.php        # จัดการคิว
│   ├── settings.php            # การตั้งค่าระบบ
│   └── profile.php             # โปรไฟล์ผู้ใช้
├── 
├── ajax/
│   ├── search_patient.php      # ค้นหาผู้ป่วย
│   ├── call_queue.php          # เรียกคิว
│   ├── update_status.php       # อัพเดทสถานะ
│   └── delete_queue.php        # ลบคิว
├── 
├── assets/
│   ├── css/
│   │   └── style.css           # สไตล์ CSS เพิ่มเติม
│   ├── js/
│   │   └── app.js              # JavaScript เพิ่มเติม
│   └── images/
│       └── logo.png            # โลโก้โรงพยาบาล
├── 
└── sql/
    └── pharmacy_queue.sql      # ไฟล์ฐานข้อมูล
```

## คำแนะนำการติดตั้ง

### 1. ติดตั้งฐานข้อมูล
```sql
-- นำเข้าไฟล์ sql/pharmacy_queue.sql
mysql -u root -p < pharmacy_queue.sql
```

### 2. ตั้งค่าฐานข้อมูล
แก้ไขไฟล์ `config/database.php`:
```php
private $host = 'localhost';        // เซิร์ฟเวอร์ฐานข้อมูล
private $db_name = 'pharmacy_queue'; // ชื่อฐานข้อมูล
private $username = 'root';          // ชื่อผู้ใช้
private $password = '';              // รหัสผ่าน
```

### 3. ข้อมูลการล็อกอิน
- **ผู้ดูแลระบบ:** admin / password
- **บัญชีทดสอบ:** สร้างเพิ่มได้ในหน้า Settings

### 4. คุณสมบัติหลัก

#### ส่วนหน้า (Frontend)
- ✅ สร้างคิวใหม่ด้วย HN
- ✅ ค้นหาแสดงชื่อผู้ป่