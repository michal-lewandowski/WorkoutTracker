/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: 'http://php:80/api/:path*', // Proxy to Backend
      },
    ]
  },
}

module.exports = nextConfig
