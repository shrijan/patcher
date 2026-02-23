import { EventEmitter } from 'events'

export const eventHandler = new EventEmitter()

eventHandler.setMaxListeners(20)
