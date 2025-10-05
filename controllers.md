# SafeRide API Documentation

## Table of Contents
1. [Authentication](#authentication)
2. [User Management](#user-management)
3. [Driver Operations](#driver-operations)
4. [Passenger Operations](#passenger-operations)
5. [Ride Management](#ride-management)
6. [Admin Operations](#admin-operations)
7. [Payment](#payment)
8. [OTP Verification](#otp-verification)

---

## Base URL
```
Production: https://your-domain.com/api
Development: http://localhost:8000/api
```

## Authentication
All API endpoints require Bearer token authentication unless specified otherwise.

**Header Format:**
```
Authorization: Bearer {your_token_here}
```

---

## 1. Authentication

### 1.1 Register User
**Endpoint:** `POST /api/register`  
**Auth Required:** No

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "passenger"
}
```

**Validation Rules:**
- `name`: required, string, max:255
- `email`: required, email, unique
- `phone`: nullable, string, max:20
- `password`: required, string, confirmed, min:8
- `role`: required, in:passenger,driver

**Response (201):**
```json
{
  "message": "Registration successful.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "passenger"
  },
  "requires_driver_profile": false
}
```

---

### 1.2 Complete Driver Profile
**Endpoint:** `POST /api/complete-driver-profile`  
**Auth Required:** Yes (Driver only)

**Request Body (multipart/form-data):**
```json
{
  "license_number": "DL12345",
  "vehicle_type": "sedan",
  "vehicle_number": "ABC-1234",
  "car_photo_front": "(file)",
  "car_photo_back": "(file)",
  "car_photo_left": "(file)",
  "car_photo_right": "(file)",
  "license_photo": "(file)",
  "id_photo": "(file)",
  "insurance_photo": "(file)"
}
```

**Validation Rules:**
- `license_number`: required, string, max:50
- `vehicle_type`: required, string, max:50
- `vehicle_number`: required, string, max:50
- `car_photo_*`: required, image (jpeg,png,jpg), max:5120KB
- `license_photo`: required, image, max:5120KB
- `id_photo`: required, image, max:5120KB
- `insurance_photo`: required, image, max:5120KB

**Response (201):**
```json
{
  "message": "Driver profile submitted. Awaiting admin approval."
}
```

---

### 1.3 Login
**Endpoint:** `POST /api/login`  
**Auth Required:** No

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "passenger"
  },
  "token": "1|abc123xyz..."
}
```

**Error Responses:**
- `401`: Invalid credentials
- `403`: Email not verified

---

### 1.4 Logout
**Endpoint:** `POST /api/logout`  
**Auth Required:** Yes

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### 1.5 Get User Profile
**Endpoint:** `GET /api/profile`  
**Auth Required:** Yes

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "passenger"
  }
}
```

---

## 2. OTP Verification

### 2.1 Send OTP
**Endpoint:** `POST /api/send-otp`  
**Auth Required:** No

**Request Body:**
```json
{
  "phone": "+1234567890"
}
```

**Response (200):**
```json
{
  "message": "OTP sent successfully"
}
```

**Error (429):** Too many requests

---

### 2.2 Resend OTP
**Endpoint:** `POST /api/resend-otp`  
**Auth Required:** No

**Request Body:**
```json
{
  "phone": "+1234567890"
}
```

**Response:** Same as Send OTP

---

### 2.3 Verify OTP
**Endpoint:** `POST /api/verify-otp`  
**Auth Required:** No

**Request Body:**
```json
{
  "phone": "+1234567890",
  "code": "123456"
}
```

**Response (200):**
```json
{
  "message": "OTP verified successfully"
}
```

**Error Responses:**
- `422`: Invalid OTP or OTP expired
- `404`: User not found

---

## 3. Driver Operations

### 3.1 List Available Drivers
**Endpoint:** `GET /api/drivers`  
**Auth Required:** Yes

**Response (200):**
```json
[
  {
    "id": 1,
    "name": "John Driver",
    "vehicle_type": "sedan",
    "vehicle_number": "ABC-1234",
    "current_driver_lat": 33.8938,
    "current_driver_lng": 35.5018,
    "availability_status": true,
    "rating": 4.5,
    "current_route": "encoded_polyline_string",
    "ride_id": 5
  }
]
```

---

### 3.2 Get Driver Profile
**Endpoint:** `GET /api/drivers/{driver}/profile`  
**Auth Required:** Yes (Driver or Admin)

**Response (200):**
```json
{
  "id": 1,
  "user_id": 5,
  "license_number": "DL12345",
  "vehicle_type": "sedan",
  "vehicle_number": "ABC-1234",
  "car_photo": "https://domain.com/storage/drivers/car.jpg",
  "license_photo": "https://domain.com/storage/drivers/license.jpg",
  "id_photo": "https://domain.com/storage/drivers/id.jpg",
  "insurance_photo": "https://domain.com/storage/drivers/insurance.jpg",
  "rating": 4.5,
  "availability_status": true,
  "current_driver_lat": 33.8938,
  "current_driver_lng": 35.5018
}
```

---

### 3.3 Update Driver Profile
**Endpoint:** `PUT /api/drivers/{driver}/profile`  
**Auth Required:** Yes (Driver or Admin)

**Request Body:**
```json
{
  "license_number": "DL54321",
  "vehicle_type": "suv",
  "vehicle_number": "XYZ-9876",
  "availability_status": true,
  "scanning_range_km": 10
}
```

**Validation Rules:**
- `license_number`: nullable, string, max:50
- `vehicle_type`: nullable, string, max:50
- `vehicle_number`: nullable, string, max:50
- `rating`: nullable, numeric, between:0,10
- `availability_status`: nullable, boolean
- `current_driver_lat`: nullable, numeric
- `current_driver_lng`: nullable, numeric
- `scanning_range_km`: nullable, numeric
- Photo fields: nullable, image, max:2048KB

**Response (302):** Redirect to driver list with success message

---

### 3.4 Update Driver Availability
**Endpoint:** `PUT /api/drivers/availability`  
**Auth Required:** Yes (Driver)

**Request Body:**
```json
{
  "availability_status": true
}
```

**Response (200):**
```json
{
  "id": 1,
  "availability_status": true
}
```

---

### 3.5 Go Online
**Endpoint:** `POST /api/drivers/{driver}/go-online`  
**Auth Required:** Yes (Driver or Admin)

**Response (200):**
```json
{
  "message": "Driver online",
  "driver": {
    "id": 1,
    "availability_status": true,
    "active_at": "2025-10-05T10:30:00Z"
  }
}
```

---

### 3.6 Go Offline
**Endpoint:** `POST /api/drivers/{driver}/go-offline`  
**Auth Required:** Yes (Driver or Admin)

**Response (200):**
```json
{
  "message": "Driver offline",
  "driver": {
    "id": 1,
    "availability_status": false,
    "inactive_at": "2025-10-05T12:30:00Z"
  }
}
```

---

### 3.7 Update Driver Location
**Endpoint:** `POST /api/drivers/{driver}/location`  
**Auth Required:** Yes (Driver or Admin)

**Request Body:**
```json
{
  "lat": 33.8938,
  "lng": 35.5018
}
```

**Validation Rules:**
- `lat`: required, numeric, between:-90,90
- `lng`: required, numeric, between:-180,180

**Response (200):**
```json
{
  "message": "Location updated & broadcasted",
  "lat": 33.8938,
  "lng": 35.5018,
  "ride_id": 5,
  "current_route": "encoded_polyline"
}
```

---

### 3.8 Update Scanning Range
**Endpoint:** `PUT /api/drivers/{driver}/range`  
**Auth Required:** Yes (Driver or Admin)

**Request Body:**
```json
{
  "scanning_range_km": 15
}
```

**Validation Rules:**
- `scanning_range_km`: required, numeric, min:1, max:50

**Response (200):**
```json
{
  "message": "Scanning range updated",
  "range": 15
}
```

---

### 3.9 Get Activity Logs
**Endpoint:** `GET /api/drivers/{driver}/activity-logs`  
**Auth Required:** Yes (Driver or Admin)

**Response (200):**
```json
[
  {
    "active_at": "2025-10-05T08:00:00",
    "inactive_at": "2025-10-05T12:00:00",
    "duration_seconds": 14400,
    "duration_human": "04:00:00"
  }
]
```

---

### 3.10 Get Live Status
**Endpoint:** `GET /api/drivers/{driver}/live-status`  
**Auth Required:** Yes (Driver or Admin)

**Response (200):**
```json
{
  "is_online": true,
  "active_since": "2025-10-05T08:00:00",
  "duration_seconds": 3600,
  "duration_human": "01:00:00"
}
```

---

### 3.11 Get Drivers for Passenger
**Endpoint:** `GET /api/drivers-for-passenger`  
**Auth Required:** Yes (Passenger)

**Description:** Returns only drivers who have accepted rides for the authenticated passenger.

**Response:** Same format as List Available Drivers

---

## 4. Passenger Operations

### 4.1 Update Passenger Location
**Endpoint:** `POST /api/passenger/location`  
**Auth Required:** Yes (Passenger)

**Request Body:**
```json
{
  "lat": 33.8938,
  "lng": 35.5018
}
```

**Validation Rules:**
- `lat`: required, numeric, between:-90,90
- `lng`: required, numeric, between:-180,180

**Response (200):**
```json
{
  "message": "Location updated"
}
```

---

### 4.2 Get My Rides
**Endpoint:** `GET /api/passenger/rides`  
**Auth Required:** Yes (Passenger)

**Description:** Get all rides for the authenticated passenger.

**Response (200):**
```json
[
  {
    "id": 1,
    "passenger_id": 3,
    "driver_id": 5,
    "origin_lat": 33.8938,
    "origin_lng": 35.5018,
    "destination_lat": 34.0058,
    "destination_lng": 36.2181,
    "status": "completed",
    "fare": 25.50,
    "created_at": "2025-10-05T10:30:00Z",
    "driver": {
      "id": 5,
      "user": {
        "name": "John Driver"
      }
    }
  }
]
```

---

### 4.3 Get Passenger Profile
**Endpoint:** `GET /api/passenger/profile`  
**Auth Required:** Yes (Passenger)

**Response (200):**
```json
{
  "user": {
    "id": 3,
    "name": "Jane Passenger",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "role": "passenger",
    "gender": "female",
    "profile_photo": "https://domain.com/storage/profiles/photo.jpg"
  }
}
```

---

### 4.4 Update Passenger Profile
**Endpoint:** `PUT /api/passenger/profile`  
**Auth Required:** Yes (Passenger)

**Request Body (multipart/form-data):**
```json
{
  "name": "Jane Smith",
  "phone": "+1234567890",
  "gender": "female",
  "profile_photo": "(file)"
}
```

**Validation Rules:**
- `name`: required, string, max:255
- `phone`: nullable, string, max:20, unique
- `gender`: nullable, in:male,female
- `profile_photo`: nullable, image (jpg,jpeg,png), max:2048KB

**Response (200):**
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 3,
    "name": "Jane Smith",
    "phone": "+1234567890",
    "gender": "female",
    "profile_photo": "profiles/abc123.jpg"
  }
}
```

---

### 4.5 Get Live Passengers
**Endpoint:** `GET /api/admin/passengers/live`  
**Auth Required:** Yes (Admin)

**Description:** Returns passengers online in the last 5 minutes.

**Response (200):**
```json
[
  {
    "id": 3,
    "name": "Jane Passenger",
    "current_lat": 33.8938,
    "current_lng": 35.5018,
    "last_location_update": "2025-10-05T10:25:00Z"
  }
]
```

---

## 5. Ride Management

### 5.1 Request a Ride
**Endpoint:** `POST /api/rides`  
**Auth Required:** Yes (Passenger)

**Request Body:**
```json
{
  "origin_lat": 33.8938,
  "origin_lng": 35.5018,
  "destination_lat": 34.0058,
  "destination_lng": 36.2181
}
```

**Validation Rules:**
- All coordinates: required, numeric

**Response (200):**
```json
{
  "id": 1,
  "passenger_id": 3,
  "origin_lat": 33.8938,
  "origin_lng": 35.5018,
  "destination_lat": 34.0058,
  "destination_lng": 36.2181,
  "status": "pending",
  "created_at": "2025-10-05T10:30:00Z"
}
```

---

### 5.2 Get Available Rides (Driver)
**Endpoint:** `GET /api/rides/available`  
**Auth Required:** Yes (Driver)

**Description:** Returns rides within driver's scanning range.

**Response (200):**
```json
[
  {
    "id": 1,
    "passenger_id": 3,
    "origin_lat": 33.8938,
    "origin_lng": 35.5018,
    "destination_lat": 34.0058,
    "destination_lng": 36.2181,
    "status": "pending"
  }
]
```

---

### 5.3 Accept Ride
**Endpoint:** `POST /api/rides/{ride}/accept`  
**Auth Required:** Yes (Driver)

**Response (200):**
```json
{
  "id": 1,
  "driver_id": 5,
  "passenger_id": 3,
  "status": "in_progress",
  "started_at": "2025-10-05T10:35:00Z"
}
```

**Error (403):** Ride already assigned to another driver

---

### 5.4 Mark Arrived
**Endpoint:** `POST /api/rides/{ride}/arrived`  
**Auth Required:** Yes (Driver - assigned to ride)

**Response (200):**
```json
{
  "id": 1,
  "status": "arrived",
  "arrived_at": "2025-10-05T10:45:00Z",
  "fare": 25.50
}
```

---

### 5.5 Cancel Ride
**Endpoint:** `POST /api/rides/{ride}/cancel`  
**Auth Required:** Yes (Passenger or assigned Driver)

**Response (200):**
```json
{
  "id": 1,
  "status": "cancelled"
}
```

---

### 5.6 Update Ride Location
**Endpoint:** `POST /api/rides/{ride}/location`  
**Auth Required:** Yes (Driver - assigned to ride)

**Request Body:**
```json
{
  "driver_lat": 33.8950,
  "driver_lng": 35.5030
}
```

**Response (200):**
```json
{
  "status": "location updated",
  "ride_id": 1,
  "driver_id": 5,
  "current_driver_lat": 33.8950,
  "current_driver_lng": 35.5030,
  "current_route": "encoded_polyline"
}
```

---

### 5.7 Estimate Fare
**Endpoint:** `POST /api/rides/estimate-fare`  
**Auth Required:** Yes

**Request Body:**
```json
{
  "distance": 10.5,
  "duration": 20
}
```

**Validation Rules:**
- `distance`: required, numeric, min:0 (in km)
- `duration`: required, numeric, min:0 (in minutes)

**Response (200):**
```json
{
  "estimated_fare": 25.50
}
```

---

## 6. Admin Operations

### 6.1 Get Live Locations
**Endpoint:** `GET /api/admin/live-locations`  
**Auth Required:** Yes (Admin)

**Response (200):**
```json
[
  {
    "type": "driver",
    "id": 1,
    "name": "John Driver",
    "lat": 33.8938,
    "lng": 35.5018,
    "last_update": "2025-10-05T10:30:00Z"
  },
  {
    "type": "passenger",
    "id": 3,
    "name": "Jane Passenger",
    "lat": 34.0058,
    "lng": 36.2181,
    "last_update": "2025-10-05T10:29:00Z"
  }
]
```

---

### 6.2 Fare Settings

#### Get Fare Settings
**Endpoint:** `GET /api/admin/fare-settings`  
**Auth Required:** Yes (Admin)

**Response (200):**
```json
{
  "id": 1,
  "base_fare": 5.00,
  "per_km_rate": 1.50,
  "per_minute_rate": 0.50,
  "minimum_fare": 8.00,
  "peak_multiplier": 1.2
}
```

#### Update Fare Settings
**Endpoint:** `PUT /api/admin/fare-settings`  
**Auth Required:** Yes (Admin)

**Request Body:**
```json
{
  "base_fare": 6.00,
  "per_km_rate": 1.75,
  "per_minute_rate": 0.60
}
```

**Validation Rules:**
- All fields: required, numeric, min:0

**Response (200):**
```json
{
  "message": "Fare settings updated successfully",
  "settings": { ... }
}
```

---

## 7. Payment

### 7.1 Pay for Ride
**Endpoint:** `POST /api/rides/{ride}/pay`  
**Auth Required:** Yes

**Request Body:**
```json
{
  "payment_method_id": "pm_1234567890"
}
```

**Response (200):**
```json
{
  "id": 1,
  "ride_id": 5,
  "amount": 25.50,
  "status": "paid",
  "payment_method": "stripe",
  "transaction_id": "pi_1234567890"
}
```

---

## 8. Test Endpoints

### 8.1 Random Number
**Endpoint:** `GET /api/test/random`  
**Auth Required:** No

**Response (200):**
```json
{
  "random_number": 42
}
```

---

## Error Responses

### Standard Error Format
```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Common HTTP Status Codes
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized (not logged in)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found
- `422`: Validation Error
- `429`: Too Many Requests
- `500`: Server Error

---

## WebSocket Events

### Driver Location Updated
**Channel:** `ride.{ride_id}`  
**Event:** `DriverLocationUpdated`

**Payload:**
```json
{
  "driver_id": 1,
  "lat": 33.8938,
  "lng": 35.5018,
  "ride_id": 5,
  "current_route": "encoded_polyline"
}
```

### Passenger Location Updated
**Channel:** `passenger.{passenger_id}`  
**Event:** `PassengerLocationUpdated`

**Payload:**
```json
{
  "passenger_id": 3,
  "lat": 33.8938,
  "lng": 35.5018
}
```

### Ride Requested
**Channel:** `drivers`  
**Event:** `RideRequested`

### Ride Accepted
**Channel:** `passenger.{passenger_id}`  
**Event:** `RideAccepted`

---

## Notes

1. **Rate Limiting:** OTP endpoints limited to 3 requests per 10 minutes per phone number
2. **File Uploads:** Maximum 5MB for images, 2MB for document photos
3. **Location Precision:** Coordinates must be between -90/90 (lat) and -180/180 (lng)
4. **Timestamps:** All timestamps in ISO 8601 format (UTC)
5. **Polyline Encoding:** Route polylines use standard Google polyline encoding (precision 5)
6. **Currency:** All fare amounts in USD