// ============================================
// API Client
// Centralized fetch wrapper with authentication
// ============================================

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api/v1';

// ============================================
// Custom Error Classes
// ============================================

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public response?: unknown
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export class ValidationError extends ApiError {
  constructor(
    message: string,
    public errors: Record<string, string[]>
  ) {
    super(message, 400);
    this.name = 'ValidationError';
  }
}

// ============================================
// API Client Class
// ============================================

class ApiClient {
  /**
   * Get authentication token from localStorage
   */
  private getAuthToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('auth_token');
  }

  /**
   * Main request method with error handling
   */
  private async request<T>(
    endpoint: string,
    options?: RequestInit
  ): Promise<T> {
    const token = this.getAuthToken();

    const response = await fetch(`${API_URL}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options?.headers,
      },
    });

    // Handle 401 Unauthorized - auto logout
    if (response.status === 401) {
      if (typeof window !== 'undefined') {
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
      }
      throw new ApiError('Unauthorized', 401);
    }

    // Handle 400 Validation Error
    if (response.status === 400) {
      const error = await response.json();
      throw new ValidationError(
        error.message || 'Validation failed',
        error.errors || {}
      );
    }

    // Handle other HTTP errors
    if (!response.ok) {
      const error = await response.json().catch(() => ({
        message: 'Unknown error occurred',
      }));
      throw new ApiError(
        error.message || `HTTP error ${response.status}`,
        response.status,
        error
      );
    }

    // Handle 204 No Content
    if (response.status === 204) {
      return null as T;
    }

    // Parse and return JSON response
    return response.json();
  }

  /**
   * GET request
   */
  async get<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'GET' });
  }

  /**
   * POST request
   */
  async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * PUT request
   */
  async put<T>(endpoint: string, data: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  /**
   * DELETE request
   */
  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }
}

// Export singleton instance
export const apiClient = new ApiClient();

// ============================================
// Helper function for SWR fetcher
// ============================================

export const swrFetcher = <T = unknown>(url: string): Promise<T> => 
  apiClient.get<T>(url);

