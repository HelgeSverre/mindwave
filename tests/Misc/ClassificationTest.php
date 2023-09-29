<?php

use Mindwave\Mindwave\Facades\Mindwave;

it('Can use classify function to quickly classify whatever into a classification', function () {

    $classes = [
        'fruits_vegetables',
        'grains_cereals',
        'dairy',
        'meat_poultry',
        'seafood',
        'beverages',
        'sweets_snacks',
        'condiments_sauces',
        'baked_goods',
    ];

    expect(Mindwave::classify('apple', $classes))->toEqual('fruits_vegetables');
    expect(Mindwave::classify('chicken breast', $classes))->toEqual('meat_poultry');
    expect(Mindwave::classify('kyllingvinger', $classes))->toEqual('meat_poultry');
    expect(Mindwave::classify('Pepsi Max 4stk x 1,5l, 6l', $classes))->toEqual('beverages');
    expect(Mindwave::classify('Melkesjokolade 200g Freia', $classes))->toEqual('sweets_snacks');
    expect(Mindwave::classify('Biola Syrnet Melk Blåbær 1000g Tine', $classes))->toEqual('dairy');

});

it('Can use classify function to quickly classify an input using an enum.', function () {

    enum FoodCategories: string
    {
        case fruitsVegetables = 'fruits_vegetables';
        case grainsCereals = 'grains_cereals';
        case dairy = 'dairy';
        case meatPoultry = 'meat_poultry';
        case seafood = 'seafood';
        case beverages = 'beverages';
        case sweetsSnacks = 'sweets_snacks';
        case condimentsSauces = 'condiments_sauces';
        case bakedGoods = 'baked_goods';
    }

    expect(Mindwave::classify('tomato', FoodCategories::class))->toEqual(FoodCategories::fruitsVegetables);
    expect(Mindwave::classify('chicken breast', FoodCategories::class))->toEqual(FoodCategories::meatPoultry);
    expect(Mindwave::classify('Pepsi Max 4stk x 1,5l, 6l', FoodCategories::class))->toEqual(FoodCategories::beverages);
    expect(Mindwave::classify('Melkesjokolade 200g Freia', FoodCategories::class))->toEqual(FoodCategories::sweetsSnacks);
    expect(Mindwave::classify('Biola Syrnet Melk Blåbær 1000g Tine', FoodCategories::class))->toEqual(FoodCategories::dairy);
});
