<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $license_number
 * @property string $vehicle_type
 * @property string|null $vehicle_number
 * @property numeric $rating
 * @property bool $availability_status
 * @property string|null $car_photo
 * @property string|null $car_photo_front
 * @property string|null $car_photo_back
 * @property string|null $car_photo_left
 * @property string|null $car_photo_right
 * @property string|null $license_photo
 * @property string|null $id_photo
 * @property string|null $insurance_photo
 * @property numeric|null $current_driver_lat
 * @property numeric|null $current_driver_lng
 * @property numeric|null $scanning_range_km
 * @property \Illuminate\Support\Carbon|null $active_at
 * @property \Illuminate\Support\Carbon|null $inactive_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DriverActiveDuration> $activeDurations
 * @property-read int|null $active_durations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ride> $rides
 * @property-read int|null $rides_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereActiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereAvailabilityStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCarPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCarPhotoBack($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCarPhotoFront($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCarPhotoLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCarPhotoRight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCurrentDriverLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCurrentDriverLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereIdPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereInactiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereInsurancePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereLicensePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereScanningRangeKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereVehicleNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereVehicleType($value)
 */
	class Driver extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $driver_id
 * @property string $active_at
 * @property string|null $inactive_at
 * @property int $duration_seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereActiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereInactiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverActiveDuration whereUpdatedAt($value)
 */
	class DriverActiveDuration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $driver_id
 * @property int $passenger_id
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @property-read \App\Models\User $passenger
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger wherePassengerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverBlockedPassenger whereUpdatedAt($value)
 */
	class DriverBlockedPassenger extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $phone
 * @property string|null $relationship
 * @property int $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereUserId($value)
 */
	class EmergencyContact extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $base_fare
 * @property string $per_km_rate
 * @property string $per_minute_rate
 * @property string $minimum_fare
 * @property string $cancellation_fee
 * @property float $peak_multiplier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings whereBaseFare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings whereCancellationFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings whereMinimumFare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings wherePeakMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings wherePerKmRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings wherePerMinuteRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FareSettings whereUpdatedAt($value)
 */
	class FareSettings extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $lat
 * @property string $lng
 * @property string $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FavoritePlace whereUserId($value)
 */
	class FavoritePlace extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $phone
 * @property string $code
 * @property string $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereUpdatedAt($value)
 */
	class Otp extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ride_id
 * @property string $amount
 * @property string $status
 * @property string $payment_method
 * @property string|null $transaction_id
 * @property string $currency
 * @property string|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Ride $ride
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereRideId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $type
 * @property string $value
 * @property int|null $max_uses
 * @property int $used_count
 * @property string|null $min_fare
 * @property \Illuminate\Support\Carbon|null $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereMaxUses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereMinFare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereUsedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereValue($value)
 */
	class PromoCode extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $passenger_id
 * @property int|null $driver_id
 * @property string $origin_lat
 * @property string $origin_lng
 * @property string $destination_lat
 * @property string $destination_lng
 * @property string $status
 * @property string|null $fare
 * @property float|null $distance
 * @property float|null $duration
 * @property string|null $accepted_at
 * @property string|null $started_at
 * @property string|null $arrived_at
 * @property string|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $promo_code_id
 * @property string $discount
 * @property string|null $final_fare
 * @property bool $sos_triggered
 * @property \Illuminate\Support\Carbon|null $sos_triggered_at
 * @property-read \App\Models\Driver|null $driver
 * @property-read \App\Models\FareSettings|null $fareSetting
 * @property-read mixed $calculated_fare
 * @property-read mixed $current_driver_lat
 * @property-read mixed $current_driver_lng
 * @property-read mixed $durations
 * @property-read \App\Models\User $passenger
 * @property-read \App\Models\Payment|null $payments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RideLog> $rideLogs
 * @property-read int|null $ride_logs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereArrivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDestinationLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDestinationLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereFare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereFinalFare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereOriginLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereOriginLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride wherePassengerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride wherePromoCodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereSosTriggered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereSosTriggeredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ride whereUpdatedAt($value)
 */
	class Ride extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ride_id
 * @property string|null $driver_lat
 * @property string|null $driver_lng
 * @property string|null $passenger_lat
 * @property string|null $passenger_lng
 * @property int|null $pickup_duration_seconds
 * @property int|null $trip_duration_seconds
 * @property int|null $total_duration_seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Ride $ride
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereDriverLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereDriverLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog wherePassengerLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog wherePassengerLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog wherePickupDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereRideId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereTotalDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereTripDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RideLog whereUpdatedAt($value)
 */
	class RideLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $phone
 * @property string $password
 * @property bool $is_verified
 * @property string|null $verification_code
 * @property \Illuminate\Support\Carbon|null $verification_code_expires_at
 * @property string|null $remember_token
 * @property numeric $wallet_balance
 * @property string|null $fcm_token
 * @property string $gender
 * @property string $role
 * @property string|null $profile_photo
 * @property bool $status
 * @property bool $is_locked
 * @property numeric|null $current_lat
 * @property numeric|null $current_lng
 * @property string|null $last_location_update
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver|null $driver
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ride> $rides
 * @property-read int|null $rides_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFcmToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLocationUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVerificationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVerificationCodeExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWalletBalance($value)
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

