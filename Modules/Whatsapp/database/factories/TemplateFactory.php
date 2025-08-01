<?php

namespace Modules\Whatsapp\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Whatsapp\Models\Template;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition()
    {
        return [
            'fbid' => $this->faker->unique()->numerify('template_################'),
            'name' => $this->faker->unique()->slug(3),
            'language' => $this->faker->randomElement(['en_US', 'en', 'es', 'fr', 'de']),
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => $this->faker->sentence()
                ]
            ],
            'parameter_format' => $this->faker->randomElement(['POSITIONAL', 'NAMED']),
            'status' => $this->faker->randomElement(['APPROVED', 'IN REVIEW', 'DRAFT', 'DISAPPROVED']),
            'api_status' => $this->faker->randomElement(['APPROVED', 'PENDING', 'REJECTED']),
            'category' => $this->faker->randomElement(['UTILITY', 'MARKETING']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'APPROVED',
                'api_status' => 'APPROVED',
            ];
        });
    }

    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'DRAFT',
                'api_status' => null,
            ];
        });
    }

    public function disapproved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'DISAPPROVED',
                'api_status' => 'REJECTED',
            ];
        });
    }

    public function inReview()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'IN REVIEW',
                'api_status' => 'PENDING',
            ];
        });
    }
}