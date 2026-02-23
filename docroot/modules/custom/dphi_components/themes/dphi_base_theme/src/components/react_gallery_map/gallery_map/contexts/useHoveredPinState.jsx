import { createContext, useContext, useState } from 'react'

const HoveredPinContext = createContext()

export const HoveredPinProvider = ({ children }) => {
  const [hoveredPin, setHoveredPin] = useState(null)
  return (
    <HoveredPinContext.Provider value={{ hoveredPin, setHoveredPin }}>
      {children}
    </HoveredPinContext.Provider>
  )
}

export const useHoveredPin = () => {
  const context = useContext(HoveredPinContext)
  if (context === undefined) {
    throw new Error('useHoveredPin must be used within a HoveredPinProvider')
  }
  return context
}
