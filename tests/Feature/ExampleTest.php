<?php

/** @var Tests\TestCase $this */ //

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
