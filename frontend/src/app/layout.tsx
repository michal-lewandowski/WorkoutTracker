import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'Symfony + Next.js App',
  description: 'Frontend aplikacja Next.js połączona z backend Symfony',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="pl">
      <body>{children}</body>
    </html>
  )
}
