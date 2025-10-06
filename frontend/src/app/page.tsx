'use client'

import { useState, useEffect } from 'react'

export default function Home() {
  const [apiResponse, setApiResponse] = useState<string>('')
  const [loading, setLoading] = useState(false)

  const testApiConnection = async () => {
    setLoading(true)
    try {
      const response = await fetch('/api/test')
      const data = await response.json()
      setApiResponse(JSON.stringify(data, null, 2))
    } catch (error) {
      setApiResponse('Błąd połączenia z API: ' + (error as Error).message)
    }
    setLoading(false)
  }

  return (
    <div className="container">
      <main className="main">
        <h1 className="title">Hello World!</h1>
        <h2 className="subtitle">Symfony + Next.js</h2>
        
        <p className="description">
          Witaj w aplikacji frontendowej Next.js połączonej z backendem Symfony!
          Ta aplikacja wykorzystuje Docker do zarządzania mikroserwisami.
        </p>

        <div className="api-info">
          <h3>Testowanie połączenia z API</h3>
          <button 
            className="button" 
            onClick={testApiConnection}
            disabled={loading}
          >
            {loading ? 'Łączenie...' : 'Testuj API'}
          </button>
          
          {apiResponse && (
            <pre style={{
              marginTop: '1rem',
              padding: '1rem',
              background: '#1e293b',
              color: '#e2e8f0',
              borderRadius: '4px',
              textAlign: 'left',
              overflow: 'auto'
            }}>
              {apiResponse}
            </pre>
          )}
        </div>

        <div style={{ marginTop: '2rem' }}>
          <p><strong>Frontend:</strong> Next.js 14 (TypeScript)</p>
          <p><strong>Backend:</strong> Symfony (PHP)</p>
          <p><strong>Konteneryzacja:</strong> Docker + Docker Compose</p>
        </div>
      </main>
    </div>
  )
}
