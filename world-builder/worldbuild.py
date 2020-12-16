#####################################################
#  Welcome to the interactive builder. This program #
#  will simulate you as a player, you will be able  #
#  to walk between rooms as well as add them in     #
#  and see them from where you are.                 #
#####################################################

#######################################################
# Instructions for the commandline interface:         #
# Room names must contain no spaces, rather they      #
# should be delimited by an underscore, for example   #
#               example_room_name                     #
# and that's pretty much the only current restriction #
#######################################################

world = """
{{
    "world-name": "{0}",
    "rooms": {{
                {1}
    }}
}}
"""

class RoomBuilder():
    def __init__(self):
        self.currentRoom = None
        self.rooms = []
    def move(self):
        pass #change active room
    def edit(self, thing, to):
        if thing == 'roomId':
            return 'You cannot edit that'
        elif thing == 'exits':
            return 'Please use deleteExit()'
        else:
            setattr(self.currentRoom, thing, to)
    def addRoom(self, roomId, desc, spawn, temp, oxygen_level, lightLevel, terrainType):
        vars()[roomId] = Room(roomId, desc, spawn, temp, oxygen_level, lightLevel, terrainType)
        self.rooms.append(vars()[roomId])
        self.currentRoom = vars()[roomId]
    def build(self):
        pass #build every room object here and print in all nice into the world reference
             #on a build, before calling the Room.generate() method, change the endType of the last room in the list to '}'


class Room():
    def __init__(self, roomId: str, desc: str, spawn: bool, temperature: int, oxygen_level: int, lightLevel: int, terrainType: str, endType='},'):
        self.roomId = roomId
        self.exits = []
        self.description = desc
        self.spawn = spawn
        self.temperature = temperature
        self.oxygen_level = oxygen_level
        self.lightLevel = lightLevel
        self.terrainType = terrainType
        self.endType = endType
    def addExit(self, direction, target, desc, flag, keywords, keyname):
        self.exits.append([direction, target, desc, flag, keywords, keyname])
    def generate(self):
        #append all teh pretty json here
        #we need to know if we have more than 1 exit, to properly append commas and commas
        self.temp = ""
        if len(self.exits) >= 2:
            self.totalExits = len(self.exits)
        for i in range(0, len(self.exits)):
            if i == self.totalExits - 1:
                isLastExit = '}'
            else:
                isLastExit = '},'
            self.temp += EXITS.format(self.exits[i][0], self.exits[i][1], self.exits[i][2], self.exits[i][3], self.exits[i][4], self.exits[i][5], isLastExit)
        return REFERENCE.format(self.roomId, self.spawn, self.description, self.temp, self.temperature, self.oxygen_level, self.lightLevel, '', self.terrainType, self.endType)


REFERENCE = """
"{0}": {{
    "room-name": "{0}",
    "spawn": "{1}",
    "description": "{2}",
    "exits" => {{
        {3}
    }},
    "temperature": {4},
    "oxygen_level": {5},
    "light_level": {6},
    "env": [
           "{7}"
     ],
     "terrain": "{8}"
{9}
"""

EXITS = """
"{0}": {{
            "target": "{1}",
            "description": "{2}",
            "flag": "{3}",
            "keywords": [
                {4}
            ],
            "key-name": "{5}",
            "extras": []{6}
"""
#room-name is a string, replacing empty space with _
#spawn is a bool that states whether it is the world spawn or not
#description is a string that describes the room you're in
#flag is whether the room is unrestricted or not
#dir is a full cardinal direction [south, north, east, west]
#target is the room the direction goes to
#key-name is the id of the key that opens the door, or otherwise blank
#keywords is blank for now
#temperature is the temperature in Farenheigt
#oxygen_level is the level of o2 in the room 0-100
#light_level is the level of light in the room 50 being normal, 0 being pitch black, 100 being blindingly bright
#env is the environment type <DARK|DEATH|NOMOB|INDOORS|PEACEFUL|SOUNDPROOF|NOTRACK|NOMAGIC|TUNNEL|PRIVATE|GODROOM>
#terrain is the terrain type <INSIDE|FIELD|CITY|FOREST|HILLS|MOUNTAINS|WATER_SWIM|WATER_NOSWIM|UNDERWATER|FLYING>

#reference 4 needs to be able to contain multiple comma-delimited strings