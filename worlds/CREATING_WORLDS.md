# Building New World Guide

## Overview

Building a new world is easy or at least it should be.  World files are created out of JSON
and contain attributes that describe the World and rooms in the world. Below are the supported World format.

## General World Attributes

`name` - **required** (string)

The display name of the World.

`biome` - **optional** (string)

The overall biome of the World.  At this point you should only specify 'default' and it does nothing.

`rooms` - **required** (array)

This array specifies the available rooms in the World.  See the `Room Attributes` section for further information.


## Room Attributes

`id` - **required** (string)

The ID of the room is used to reference this room from other places.  Really any type of ID can be used but the ID
cannot be a duplicate, so it is recommended to prefix the ID with a unique identifier.

`name` - **required** (string)

The display name of the room.

`spawn` - **optional** (true/false)

If set to true, this room becomes the default spawn point.

`keywords` - **optional** (array)

An array of keywords for which we have no use at the moment.

`description` - **required** (string)

The description of the room.  

`short-description` - **required** (string)

A shorter description of the room.  Typically used when player turns off the `description`.

`temperature` - **optional** (integer: -52..212)

The temperature in farenheit of the room.  If not set, temperature defaults to 72 degrees.

`oxygen-level` - **optional** (integer: 0..100)

The oxygen level of the room.  If not set, defaults to 100.

`light-level` - optional (integer: 0..100)

The light level in the room.  If not set, defaults to 100.

`ambiance` - optional (string)

The particular mood or experience in the room.  Defaults to "indoor" and not used yet.

`exits` - **required** (array)

An array consisting of the direction ("south", "up", etc) and other information.  See `Exit Attributes` for further information.
May have as many exits as directions exist.

## Exit Attributes

`target` - **required** (string)

A room ID

`requires` - optional

Consists of "attribute", "operator" and "value".

"Attribute": 
- "level" - level of the mob or player attempting to utilize the exit
- [@FEATURE] needs some additional attributes, like "status" or "health"?

"Operator":
- ">", ">=", "<", "<=", "=", "<>"

"Value": 
The value to compare against.  [@FEATURE] Does not support arrays at the moment.

## Example World

`
{
    "name": "Tikal",
    "biome": "default",
    "rooms": [
        {
            "id": "tikal-grand_lobby",
            "name": "Grand Lobby",
            "spawn": "true",
            "keywords": [],
            "description": "A large, ornate lobby extending upwards into an unknown fog.  The small crystalized window panes set into the stonework let in the perfect amount of light.",
            "exits": {
                "south": {
                    "target": "tikal-training-entrance"
                }
            },
            "temperature": 72,
            "oxygen-level": 100,
            "light-level": 50,
            "ambiance": "indoor"
        },
        {
            "id": "tikal-training_entrance",
            "name": "Training Entrance",
            "description": "An iron-gated entrance lying in a strange mist.",
            "keywords": [],
            "exits": {
                "north": {
                    "target": "tikal-grand_lobby"
                },
                "south": {
                    "target": "tikal-training-room",
                    "requires": {
                        "attribute": "level",
                        "operator": "<",
                        "value": "5"
                    }
                }
            },
            "temperature": 68,
            "oxygen-level": 100,
            "light-level": 50,
            "ambiance": "outdoor"
        }
    ]
}
`