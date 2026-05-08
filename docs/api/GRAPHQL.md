# 🔌 GraphQL API Documentation

MeowSEO provides full WPGraphQL integration for headless WordPress deployments.

## 📋 Prerequisites

### Required
- **WPGraphQL**: 1.14.0 or higher
- **WordPress**: 6.0 or higher
- **MeowSEO**: 1.0.0 or higher

### Installation

```bash
# Install WPGraphQL
wp plugin install wp-graphql --activate

# MeowSEO will automatically detect and integrate
```

---

## 🎯 Available Queries

### 1. SEO Meta Data

Query SEO metadata for any post type.

#### Query

```graphql
query GetPostSeo {
  post(id: "123", idType: DATABASE_ID) {
    id
    title
    seoMeta {
      title
      description
      robots
      canonical
      focusKeyword
      socialTitle
      socialDescription
      socialImage {
        sourceUrl
        altText
      }
    }
  }
}
```

#### Response

```json
{
  "data": {
    "post": {
      "id": "cG9zdDoxMjM=",
      "title": "My Post Title",
      "seoMeta": {
        "title": "Custom SEO Title",
        "description": "Custom meta description for SEO",
        "robots": "index,follow",
        "canonical": "https://example.com/my-post/",
        "focusKeyword": "seo optimization",
        "socialTitle": "Social Media Title",
        "socialDescription": "Description for social sharing",
        "socialImage": {
          "sourceUrl": "https://example.com/image.jpg",
          "altText": "Image description"
        }
      }
    }
  }
}
```

### 2. Schema.org Data

Query structured data for any post.

#### Query

```graphql
query GetPostSchema {
  post(id: "123", idType: DATABASE_ID) {
    id
    title
    schema {
      type
      jsonLd
      properties {
        name
        value
      }
    }
  }
}
```

#### Response

```json
{
  "data": {
    "post": {
      "id": "cG9zdDoxMjM=",
      "title": "My Post Title",
      "schema": {
        "type": "Article",
        "jsonLd": "{\"@context\":\"https://schema.org\",\"@type\":\"Article\",\"headline\":\"My Post Title\"}",
        "properties": [
          {
            "name": "headline",
            "value": "My Post Title"
          },
          {
            "name": "author",
            "value": "John Doe"
          }
        ]
      }
    }
  }
}
```

### 3. Breadcrumbs

Query breadcrumb trail for navigation.

#### Query

```graphql
query GetPostBreadcrumbs {
  post(id: "123", idType: DATABASE_ID) {
    id
    title
    breadcrumbs {
      items {
        text
        url
        position
      }
      schema
    }
  }
}
```

#### Response

```json
{
  "data": {
    "post": {
      "id": "cG9zdDoxMjM=",
      "title": "My Post Title",
      "breadcrumbs": {
        "items": [
          {
            "text": "Home",
            "url": "https://example.com/",
            "position": 1
          },
          {
            "text": "Blog",
            "url": "https://example.com/blog/",
            "position": 2
          },
          {
            "text": "My Post Title",
            "url": "https://example.com/my-post/",
            "position": 3
          }
        ],
        "schema": "{\"@context\":\"https://schema.org\",\"@type\":\"BreadcrumbList\"}"
      }
    }
  }
}
```

### 4. Sitemap Data

Query sitemap information.

#### Query

```graphql
query GetSitemapData {
  seoSettings {
    sitemap {
      enabled
      postTypes
      taxonomies
      excludedPosts
      lastModified
    }
  }
}
```

#### Response

```json
{
  "data": {
    "seoSettings": {
      "sitemap": {
        "enabled": true,
        "postTypes": ["post", "page"],
        "taxonomies": ["category", "post_tag"],
        "excludedPosts": [45, 67],
        "lastModified": "2026-05-08T10:30:00Z"
      }
    }
  }
}
```

### 5. Global SEO Settings

Query global SEO configuration.

#### Query

```graphql
query GetSeoSettings {
  seoSettings {
    general {
      separator
      homepageTitle
      homepageDescription
      siteVerification {
        google
        bing
        yandex
      }
    }
    social {
      facebookAppId
      twitterUsername
      defaultImage {
        sourceUrl
      }
    }
    organization {
      name
      type
      logo {
        sourceUrl
      }
      socialProfiles
    }
  }
}
```

#### Response

```json
{
  "data": {
    "seoSettings": {
      "general": {
        "separator": "|",
        "homepageTitle": "My Awesome Site",
        "homepageDescription": "Welcome to my site",
        "siteVerification": {
          "google": "abc123",
          "bing": "def456",
          "yandex": null
        }
      },
      "social": {
        "facebookAppId": "123456789",
        "twitterUsername": "@mysite",
        "defaultImage": {
          "sourceUrl": "https://example.com/default.jpg"
        }
      },
      "organization": {
        "name": "My Company",
        "type": "Organization",
        "logo": {
          "sourceUrl": "https://example.com/logo.png"
        },
        "socialProfiles": [
          "https://facebook.com/mycompany",
          "https://twitter.com/mycompany"
        ]
      }
    }
  }
}
```

---

## 🔧 Mutations

### 1. Update SEO Meta

Update SEO metadata for a post.

#### Mutation

```graphql
mutation UpdatePostSeo {
  updatePostSeo(
    input: {
      postId: 123
      title: "New SEO Title"
      description: "New meta description"
      robots: "index,follow"
      focusKeyword: "new keyword"
    }
  ) {
    success
    message
    seoMeta {
      title
      description
      robots
      focusKeyword
    }
  }
}
```

#### Response

```json
{
  "data": {
    "updatePostSeo": {
      "success": true,
      "message": "SEO meta updated successfully",
      "seoMeta": {
        "title": "New SEO Title",
        "description": "New meta description",
        "robots": "index,follow",
        "focusKeyword": "new keyword"
      }
    }
  }
}
```

### 2. Update Schema

Update schema data for a post.

#### Mutation

```graphql
mutation UpdatePostSchema {
  updatePostSchema(
    input: {
      postId: 123
      schemaType: "Article"
      properties: [
        { name: "headline", value: "My Article" }
        { name: "author", value: "John Doe" }
      ]
    }
  ) {
    success
    message
    schema {
      type
      jsonLd
    }
  }
}
```

---

## 📊 Type Definitions

### SeoMeta Type

```graphql
type SeoMeta {
  title: String
  description: String
  robots: String
  canonical: String
  focusKeyword: String
  socialTitle: String
  socialDescription: String
  socialImage: MediaItem
  ogType: String
  twitterCard: String
}
```

### Schema Type

```graphql
type Schema {
  type: String!
  jsonLd: String!
  properties: [SchemaProperty]
}

type SchemaProperty {
  name: String!
  value: String
}
```

### Breadcrumbs Type

```graphql
type Breadcrumbs {
  items: [BreadcrumbItem]!
  schema: String
}

type BreadcrumbItem {
  text: String!
  url: String!
  position: Int!
}
```

### SeoSettings Type

```graphql
type SeoSettings {
  general: GeneralSettings
  social: SocialSettings
  organization: OrganizationSettings
  sitemap: SitemapSettings
}

type GeneralSettings {
  separator: String
  homepageTitle: String
  homepageDescription: String
  siteVerification: SiteVerification
}

type SocialSettings {
  facebookAppId: String
  twitterUsername: String
  defaultImage: MediaItem
}

type OrganizationSettings {
  name: String
  type: String
  logo: MediaItem
  socialProfiles: [String]
}
```

---

## 🎯 Usage Examples

### Next.js Example

```javascript
import { gql, useQuery } from '@apollo/client';

const GET_POST_SEO = gql`
  query GetPostSeo($id: ID!) {
    post(id: $id, idType: DATABASE_ID) {
      id
      title
      seoMeta {
        title
        description
        socialImage {
          sourceUrl
        }
      }
    }
  }
`;

function PostPage({ postId }) {
  const { data, loading, error } = useQuery(GET_POST_SEO, {
    variables: { id: postId },
  });

  if (loading) return <p>Loading...</p>;
  if (error) return <p>Error: {error.message}</p>;

  const { post } = data;
  const seo = post.seoMeta;

  return (
    <>
      <Head>
        <title>{seo.title || post.title}</title>
        <meta name="description" content={seo.description} />
        <meta property="og:image" content={seo.socialImage?.sourceUrl} />
      </Head>
      <article>
        <h1>{post.title}</h1>
        {/* Post content */}
      </article>
    </>
  );
}
```

### Gatsby Example

```javascript
import { graphql } from 'gatsby';

export const query = graphql`
  query PostQuery($id: String!) {
    wpPost(id: { eq: $id }) {
      id
      title
      seoMeta {
        title
        description
        socialImage {
          sourceUrl
        }
      }
    }
  }
`;

export default function PostTemplate({ data }) {
  const { wpPost } = data;
  const seo = wpPost.seoMeta;

  return (
    <>
      <Helmet>
        <title>{seo.title || wpPost.title}</title>
        <meta name="description" content={seo.description} />
      </Helmet>
      <article>
        <h1>{wpPost.title}</h1>
      </article>
    </>
  );
}
```

### React (Apollo Client) Example

```javascript
import { ApolloClient, InMemoryCache, gql } from '@apollo/client';

const client = new ApolloClient({
  uri: 'https://yoursite.com/graphql',
  cache: new InMemoryCache(),
});

async function getPostSeo(postId) {
  const { data } = await client.query({
    query: gql`
      query GetPostSeo($id: ID!) {
        post(id: $id, idType: DATABASE_ID) {
          seoMeta {
            title
            description
          }
        }
      }
    `,
    variables: { id: postId },
  });

  return data.post.seoMeta;
}
```

---

## 🔒 Authentication

### Public Queries

Most SEO data is public and doesn't require authentication:
- SEO meta
- Schema data
- Breadcrumbs
- Public settings

### Protected Mutations

Mutations require authentication:

```javascript
import { ApolloClient, createHttpLink, InMemoryCache } from '@apollo/client';
import { setContext } from '@apollo/client/link/context';

const httpLink = createHttpLink({
  uri: 'https://yoursite.com/graphql',
});

const authLink = setContext((_, { headers }) => {
  const token = localStorage.getItem('authToken');
  return {
    headers: {
      ...headers,
      authorization: token ? `Bearer ${token}` : '',
    },
  };
});

const client = new ApolloClient({
  link: authLink.concat(httpLink),
  cache: new InMemoryCache(),
});
```

---

## 🎨 GraphiQL Playground

### Access GraphiQL

```
https://yoursite.com/graphql
```

### Example Queries to Try

```graphql
# Get all posts with SEO data
query GetAllPosts {
  posts(first: 10) {
    nodes {
      id
      title
      seoMeta {
        title
        description
      }
    }
  }
}

# Get homepage SEO
query GetHomepageSeo {
  seoSettings {
    general {
      homepageTitle
      homepageDescription
    }
  }
}

# Get post with full SEO data
query GetFullPostSeo($id: ID!) {
  post(id: $id, idType: DATABASE_ID) {
    id
    title
    content
    seoMeta {
      title
      description
      robots
      canonical
      focusKeyword
      socialTitle
      socialDescription
      socialImage {
        sourceUrl
        altText
      }
    }
    schema {
      type
      jsonLd
    }
    breadcrumbs {
      items {
        text
        url
        position
      }
    }
  }
}
```

---

## 📚 Additional Resources

### WPGraphQL Documentation
- [WPGraphQL Docs](https://www.wpgraphql.com/docs/introduction)
- [GraphQL Spec](https://graphql.org/learn/)
- [Apollo Client](https://www.apollographql.com/docs/react/)

### MeowSEO Resources
- [REST API Documentation](REST_API.md)
- [Developer Guide](../developer/GETTING_STARTED.md)
- [Contributing Guide](../../CONTRIBUTING.md)

---

## 🐛 Troubleshooting

### GraphQL endpoint returns 404

**Solution**:
```bash
# Flush permalinks
wp rewrite flush

# Or in WordPress admin:
# Settings > Permalinks > Save Changes
```

### "Field not found" error

**Solution**:
```bash
# Clear GraphQL schema cache
wp graphql clear-schema

# Or deactivate/reactivate MeowSEO
wp plugin deactivate meowseo
wp plugin activate meowseo
```

### Authentication errors

**Solution**:
```bash
# Check if JWT authentication is enabled
# Install WPGraphQL JWT Authentication plugin
wp plugin install wp-graphql-jwt-authentication --activate
```

---

**Status**: ✅ Fully Documented  
**Integration**: WPGraphQL 1.14.0+  
**Support**: Headless WordPress ready
