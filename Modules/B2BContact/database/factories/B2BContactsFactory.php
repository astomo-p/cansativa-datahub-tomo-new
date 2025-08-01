<?php

namespace Modules\B2BContact\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class B2BContactsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\B2BContact\Models\B2BContacts::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'contact_name' => $this->faker->company(),
            'contact_no'   => $this->faker->numberBetween(10000000, 99999999),
            'address'      => $this->faker->address(),
            'post_code'    => $this->faker->postcode(),
            'city'         => $this->faker->city(),
            'country'      => $this->faker->country(),
            'contact_type_id'      => $this->faker->numberBetween(1, 3),
            'vat_id'   => $this->faker->numberBetween(10000000, 99999999),
        ];
    }
}

