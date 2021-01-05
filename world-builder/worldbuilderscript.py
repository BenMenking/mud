from worldbuild import *

# Function requirements
# addRoom(self, roomId, desc, isSpawn, temp, oxygen_level, lightLevel, terrainType)
# addExit(self, direction, target, desc, flag, keywords, keyname)


# Initialize and get world name
generating = True
handler = RoomBuilder()
print("Welcome to the world builder")
world_name = input("What do wish to name thy world")

#Build spawn room
print('Let us build your spawn room')
handler.addRoom('spawn', input('Please describe this room'), True, input('Please input the temperature'), input('Please enter the oxygen level'), input('Please enter the light level'), input('Please enter the terrain type'))
print('Spawn generated, moving on')

#Room generation loop
while generating == True:
    nextStep = input('What would you like to do next(room name/list exits/add exit/remove exit/add room/list rooms/change room/end)')
    if nextStep == 'list exits':
        handler.listExits()
    elif nextStep == 'add exit':
        pass
    elif nextStep == 'remove exit':
        pass
    elif nextStep == 'add room':
        hereRoom = handler.currentRoom.roomId
        roomDir = input('Which direction should we build the room in? ')
        trgt = input('What is the name of the new room')
        trgtDesc = input('What should a player see when he looks in this direction')
        handler.addExit(roomDir, trgt, trgtDesc, '', '', '')
        handler.addRoom(trgt, input('Please describe the new room'), False, input('Please input the temperature'), input('Please enter the oxygen level'), input('Please enter the light level'), input('Please enter the terrain type'))
        NiceLittleAutoMover = input('Would you like to switch to that room? Yes to switch, No to stay')
        if NiceLittleAutoMover.lower() == 'yes':
            handler.move(trgt)
            print('We are now in:')
            print(handler.currentRoom.roomId)
        elif NiceLittleAutoMover.lower() == 'no':
            print('Staying here then')
            handler.move(hereRoom)
    elif nextStep == 'list rooms':
        handler.listRooms()
    elif nextStep == 'change room':
        handler.move(input("What room do you wish to move to"))
        print('Room switched to:')
        print(handler.currentRoom.roomId)
    elif nextStep == 'room name':
        print(handler.currentRoom.roomId)
    elif nextStep == 'end':
        generating = False
    else:
        print('We don\'t recongnize that command')


#print(handler.build(world_name))