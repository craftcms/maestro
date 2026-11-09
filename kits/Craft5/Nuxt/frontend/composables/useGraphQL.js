import { useRuntimeConfig } from '#app'

export function useGraphQL() {
  const config = useRuntimeConfig()

  const query = async (query, variables = {}, options = {}) => {
    try {
      if (!config.public.CRAFT_URL) {
        throw new Error('CRAFT_URL is not configured')
      }

      let apiUrl = `${config.public.CRAFT_URL}/api`

      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }

      // Add auth header if private flag is true
      if (options.private) {
        headers['Authorization'] = `Bearer ${config.public.GRAPHQL_TOKEN}`
      }

      if (options.previewToken) {
        headers['X-Craft-Token'] = options.previewToken
      }

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          query,
          variables
        }),
        credentials: 'include'
      })

      if (!response.ok) {
        const errorText = await response.text()
        throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`)
      }

      const result = await response.json()

      if (!result || typeof result !== 'object') {
        throw new Error('Invalid response format')
      }

      if (result.errors) {
        throw new Error(result.errors[0]?.message || 'GraphQL error')
      }

      return JSON.parse(JSON.stringify(result.data))
    } catch (err) {
      console.error('GraphQL Error:', {
        message: err.message,
        craftUrl: config.public.CRAFT_URL
      })
      throw err
    }
  }

  return { query }
}
