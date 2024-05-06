<?php
// tests/Feature/ReservationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReservationTest extends TestCase
{
    use RefreshDatabase;


    protected function authenticate()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        return $token;
    }

    public function test_reservation_creation_with_valid_token()
    {
        $token = $this->authenticate();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/reservations', [
            'name' => 'John Doe',
            'phoneNumber' => '1234567890',
            'date' => '2021-01-01',
            'adults' => 2,
            'children' => 1,
            'cabin' => 1,
            'nights' => 3,
            'amountUSD' => 200,
            'amountCRC' => 106000,
            'agency' => 'Directo',
            'commission' => 0,
            'paidToUlisesUSD' => 200,
            'paidToDeyaniraUSD' => 0,
            'paidToUlisesCRC' => 0,
            'paidToDeyaniraCRC' => 106000,
            'invoiceNeeded' => true,
            'note' => 'Test reservation'
        ]);

        info('1');

        $response->assertStatus(201);
        // $response->assertJson(['message' => 'Reserva registrada con éxito']);
    }

    /**
     * Test reservation creation attempt without authentication.
     */
    public function test_reservation_creation_without_token()
    {
        $response = $this->postJson('/api/v1/reservations', [
            'name' => 'John Doe',
            'phoneNumber' => '1234567890',
            'date' => '2021-01-01',
            'adults' => 2,
            'children' => 1,
            'cabin' => 1,
            'nights' => 3,
            'amountUSD' => 200,
            'amountCRC' => 106000,
            'agency' => 'Directo',
            'commission' => 0,
            'paidToUlisesUSD' => 200,
            'paidToDeyaniraUSD' => 0,
            'paidToUlisesCRC' => 0,
            'paidToDeyaniraCRC' => 106000,
            'invoiceNeeded' => true,
            'note' => 'Test reservation'
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    /**
     * Test reservation creation with an expired or invalid token.
     */
    public function test_reservation_creation_with_invalid_token()
    {
        $this->withHeaders([
            'Authorization' => 'Bearer invalid_or_expired_token'
        ])->postJson('/api/v1/reservations', [
            'name' => 'John Doe',
            'phoneNumber' => '1234567890',
            'date' => '2021-01-01',
            'adults' => 2,
            'children' => 1,
            'cabin' => 1,
            'nights' => 3,
            'amountUSD' => 200,
            'amountCRC' => 106000,
            'agency' => 'Directo',
            'commission' => 0,
            'paidToUlisesUSD' => 200,
            'paidToDeyaniraUSD' => 0,
            'paidToUlisesCRC' => 0,
            'paidToDeyaniraCRC' => 106000,
            'invoiceNeeded' => true,
            'note' => 'Test reservation'
        ])->assertStatus(401); // Unauthorized
    }

    public function test_reservation_creation_with_invalid_data()
    {
        $token = $this->authenticate();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/reservations', [
            'name' => '',  // Campo inválido
            'phoneNumber' => '1234567890',
            'date' => '2021-01-01',
            'adults' => 0,  // Valor inválido según las reglas asumidas
            'children' => 1,
            'cabin' => 1,
            'nights' => 3,
            'amountUSD' => 200,
            'amountCRC' => 106000,
            'agency' => 'Directo',
            'commission' => 0,
            'paidToUlisesUSD' => 200,
            'paidToDeyaniraUSD' => 0,
            'paidToUlisesCRC' => 0,
            'paidToDeyaniraCRC' => 106000,
            'invoiceNeeded' => true,
            'note' => 'Test reservation'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'adults']);
    }
}
