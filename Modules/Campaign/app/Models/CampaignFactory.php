<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\NewContactData\Models\ContactTypes;
use Modules\User\Models\User;

class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Using 1 as the default contact_type_id if no ContactTypes are found.
        // IMPORTANT: You MUST ensure that a record with ID = 1 exists in your 'contact_types' table.
        $contactTypeId = ContactTypes::inRandomOrder()->first()?->id ?? 1;

        // Ensure at least one User exists.
        // Make sure UserSeeder runs before CampaignSeeder, or User::factory() is defined.
        $userId = User::inRandomOrder()->first()?->id
                  ?? User::factory()->create()->id; // This line still relies on UserFactory, if User doesn't exist

        return [
            'contact_type_id' => $contactTypeId, // Will be 1 if no ContactTypes found
            'recipient_type' => $this->faker->randomElement(['b2b', 'b2c']),
            'campaign_name' => $this->faker->sentence(3),
            'brevo_campaign_id' => $this->faker->optional()->randomNumber(5),
            'filters' => json_encode($this->faker->optional()->passthrough([
                'type' => $this->faker->randomElement(['keyword', 'location', 'category']),
                'value' => $this->faker->word().$this->faker->numberBetween(1, 99),
                'active' => $this->faker->boolean(),
            ])),
            'channel' => $this->faker->randomElement(['email', 'sms', 'push']),
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }
}
