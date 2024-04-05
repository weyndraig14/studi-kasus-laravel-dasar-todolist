<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TodolistControllerTest extends TestCase
{
    public function testTodolist()
    {
        $this->withSession([
            "user" => "agung",
            "todolist" => [
                [
                    "id" => "1",
                    "todo" => "Vior"
                ],
                [
                    "id" => "2",
                    "todo" => "Okto"
                ]
            ]   
        ])->get('/todolist')
        ->assertSeeText("1")
        ->assertSeeText("Vior")
        ->assertSeeText("2")
        ->assertSeeText("Okto");
    }

    public function testAddTodoFailed()
    {
        $this->withSession([
            "user" => "agung"
        ])->post("/todolist", [])
        ->assertSeeText("Todo is required");
    }

    public function testAddTodoSuccess()
    {
        $this->withSession([
            "user" => "agung"
        ])->post("/todolist", [
            "todo" => "Vior"
        ])->assertRedirect("/todolist");
    }

    public function testRemoveTodolist()
    {
        $this->withSession([
            "user" => "agung",
            "todolist" => [
                [
                    "id" => "1",
                    "todo" => "Vior"
                ],
                [
                    "id" => "2",
                    "todo" => "Okto"
                ]
            ]
        ])->post("/todolist/1/delete")
        ->assertRedirect("/todolist");
    }
}
