<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Measurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'customer_name',
        'telephone_no',
        'pant_quantity',

        // Shirt
        'shirt_length',
        'correct_chest',
        'hip_round',
        'seat_round',
        'shoulder',
        'sleeve_height',
        'bicep_round',
        'cuff_round_finish',
        'neck',
        'cuff_height',
        'fit_style',
        'front_patti_style',
        'shirt_bottom_type',
        'full_sleeve',
        'finish_chest',
        'finish_hip',
        'finish_seat',

        // Pant
        'pant_height',
        'waist',
        'seat_a',
        'thigh_f',
        'knee_f',
        'bottom_f',
        'zip_length',
        'inseam',
        'total_round',
        'pant_type',
    ];

    protected $casts = [
        'shirt_length'       => 'float',
        'correct_chest'      => 'float',
        'hip_round'          => 'float',
        'seat_round'         => 'float',
        'shoulder'           => 'float',
        'sleeve_height'      => 'float',
        'bicep_round'        => 'float',
        'cuff_round_finish'  => 'float',
        'neck'               => 'float',
        'cuff_height'        => 'float',
        'finish_chest'       => 'float',
        'finish_hip'         => 'float',
        'finish_seat'        => 'float',
        'pant_height'        => 'float',
        'waist'              => 'float',
        'seat_a'             => 'float',
        'thigh_f'            => 'float',
        'knee_f'             => 'float',
        'bottom_f'           => 'float',
        'zip_length'         => 'float',
        'inseam'             => 'float',
        'total_round'        => 'float',
    ];

    /**
     * Raw shirt measurement set, used to feed the diagram generator.
     */
    public function getShirtMeasurements(): array
    {
        return [
            'length'             => $this->shirt_length,
            'chest'              => $this->correct_chest,
            'hip'                => $this->hip_round,
            'seat'               => $this->seat_round,
            'shoulder'           => $this->shoulder,
            'sleeve_height'      => $this->sleeve_height,
            'bicep'              => $this->bicep_round,
            'cuff_round'         => $this->cuff_round_finish,
            'neck'               => $this->neck,
            'cuff_height'        => $this->cuff_height,
            'fit_style'          => $this->fit_style,
            'front_patti_style'  => $this->front_patti_style,
            'bottom_type'        => $this->shirt_bottom_type,
            'full_sleeve'        => $this->full_sleeve,
            'finish_chest'       => $this->finish_chest,
            'finish_hip'         => $this->finish_hip,
            'finish_seat'        => $this->finish_seat,
        ];
    }

    /**
     * Raw pant measurement set, used to feed the diagram generator.
     */
    public function getPantMeasurements(): array
    {
        return [
            'height'      => $this->pant_height,
            'waist'       => $this->waist,
            'seat'        => $this->seat_a,
            'thigh'       => $this->thigh_f,
            'knee'        => $this->knee_f,
            'bottom'      => $this->bottom_f,
            'zip_length'  => $this->zip_length,
            'inseam'      => $this->inseam,
            'total_round' => $this->total_round,
            'pant_type'   => $this->pant_type,
        ];
    }
}