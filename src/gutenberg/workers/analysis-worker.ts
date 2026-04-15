/**
 * Web Worker for SEO Analysis
 * 
 * Runs SEO analysis in a separate thread to avoid blocking the UI.
 * Implements 5 keyword checks and calculates a score from 0-100.
 */

interface AnalysisInput {
  title: string;
  description: string;
  content: string;
  slug: string;
  focusKeyword: string;
}

interface AnalysisResult {
  id: string;
  type: 'good' | 'ok' | 'problem';
  message: string;
}

interface AnalysisOutput {
  score: number;
  results: AnalysisResult[];
  color: 'red' | 'orange' | 'green';
}

/**
 * Extract the first paragraph from HTML content
 */
function extractFirstParagraph(content: string): string {
  // Try to extract first <p> tag content
  const pTagMatch = content.match(/<p[^>]*>(.*?)<\/p>/i);
  if (pTagMatch) {
    // Remove any remaining HTML tags from paragraph content
    return pTagMatch[1].replace(/<[^>]*>/g, ' ');
  }
  
  // Fallback: remove all HTML tags and get first paragraph
  const withoutTags = content.replace(/<[^>]*>/g, ' ');
  const paragraphs = withoutTags.split(/\n\n+/);
  return paragraphs[0] || '';
}

/**
 * Extract headings from HTML content
 */
function extractHeadings(content: string): string[] {
  const headingRegex = /<h[1-6][^>]*>(.*?)<\/h[1-6]>/gi;
  const headings: string[] = [];
  let match;
  
  while ((match = headingRegex.exec(content)) !== null) {
    // Remove any remaining HTML tags from heading text
    const headingText = match[1].replace(/<[^>]*>/g, '');
    headings.push(headingText);
  }
  
  return headings;
}

/**
 * Analyze SEO based on focus keyword
 */
export function analyzeSEO(data: AnalysisInput): AnalysisOutput {
  const { title, description, content, slug, focusKeyword } = data;
  const results: AnalysisResult[] = [];
  let score = 0;
  
  // Skip keyword checks if no focus keyword provided
  if (!focusKeyword) {
    return {
      score: 0,
      results: [],
      color: 'red',
    };
  }
  
  const keywordLower = focusKeyword.toLowerCase();
  
  // Check 1: Focus keyword in title (20 points)
  if (title.toLowerCase().includes(keywordLower)) {
    results.push({
      id: 'keyword-in-title',
      type: 'good',
      message: 'Focus keyword appears in SEO title',
    });
    score += 20;
  } else {
    results.push({
      id: 'keyword-in-title',
      type: 'problem',
      message: 'Focus keyword missing from SEO title',
    });
  }
  
  // Check 2: Focus keyword in description (20 points)
  if (description.toLowerCase().includes(keywordLower)) {
    results.push({
      id: 'keyword-in-description',
      type: 'good',
      message: 'Focus keyword appears in meta description',
    });
    score += 20;
  } else {
    results.push({
      id: 'keyword-in-description',
      type: 'problem',
      message: 'Focus keyword missing from meta description',
    });
  }
  
  // Check 3: Focus keyword in first paragraph (20 points)
  const firstParagraph = extractFirstParagraph(content);
  if (firstParagraph.toLowerCase().includes(keywordLower)) {
    results.push({
      id: 'keyword-in-first-paragraph',
      type: 'good',
      message: 'Focus keyword appears in first paragraph',
    });
    score += 20;
  } else {
    results.push({
      id: 'keyword-in-first-paragraph',
      type: 'problem',
      message: 'Focus keyword missing from first paragraph',
    });
  }
  
  // Check 4: Focus keyword in headings (20 points)
  const headings = extractHeadings(content);
  const keywordInHeadings = headings.some(h => 
    h.toLowerCase().includes(keywordLower)
  );
  if (keywordInHeadings) {
    results.push({
      id: 'keyword-in-headings',
      type: 'good',
      message: 'Focus keyword appears in at least one heading',
    });
    score += 20;
  } else {
    results.push({
      id: 'keyword-in-headings',
      type: 'problem',
      message: 'Focus keyword missing from headings',
    });
  }
  
  // Check 5: Focus keyword in URL slug (20 points)
  const slugifiedKeyword = keywordLower.replace(/\s+/g, '-');
  if (slug.includes(slugifiedKeyword)) {
    results.push({
      id: 'keyword-in-slug',
      type: 'good',
      message: 'Focus keyword appears in URL slug',
    });
    score += 20;
  } else {
    results.push({
      id: 'keyword-in-slug',
      type: 'problem',
      message: 'Focus keyword missing from URL slug',
    });
  }
  
  // Determine color based on score
  let color: 'red' | 'orange' | 'green';
  if (score < 40) {
    color = 'red';
  } else if (score < 70) {
    color = 'orange';
  } else {
    color = 'green';
  }
  
  return { score, results, color };
}

// Web Worker message handler
self.addEventListener('message', (event: MessageEvent<AnalysisInput>) => {
  const result = analyzeSEO(event.data);
  self.postMessage(result);
});
