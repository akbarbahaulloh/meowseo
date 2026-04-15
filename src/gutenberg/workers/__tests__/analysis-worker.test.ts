/**
 * Unit Tests for Analysis Worker
 * 
 * Tests specific examples and edge cases for SEO analysis.
 */

import { analyzeSEO } from '../analysis-worker';

describe('Analysis Worker - Unit Tests', () => {
  describe('analyzeSEO function', () => {
    it('should return score 0 with empty focus keyword', () => {
      const result = analyzeSEO({
        title: 'Test Title',
        description: 'Test description',
        content: '<p>Test content</p>',
        slug: 'test-slug',
        focusKeyword: '',
      });

      expect(result.score).toBe(0);
      expect(result.results).toEqual([]);
      expect(result.color).toBe('red');
    });

    it('should return score 100 when keyword appears in all locations', () => {
      const keyword = 'seo optimization';
      const result = analyzeSEO({
        title: 'SEO Optimization Guide',
        description: 'Learn about SEO optimization techniques',
        content: '<p>SEO optimization is important.</p><h2>SEO Optimization Tips</h2>',
        slug: 'seo-optimization-guide',
        focusKeyword: keyword,
      });

      expect(result.score).toBe(100);
      expect(result.color).toBe('green');
      expect(result.results).toHaveLength(5);
      expect(result.results.every(r => r.type === 'good')).toBe(true);
    });

    describe('Check 1: Keyword in title', () => {
      it('should pass when keyword is in title', () => {
        const result = analyzeSEO({
          title: 'WordPress SEO Guide',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const titleCheck = result.results.find(r => r.id === 'keyword-in-title');
        expect(titleCheck).toBeDefined();
        expect(titleCheck?.type).toBe('good');
        expect(titleCheck?.message).toBe('Focus keyword appears in SEO title');
      });

      it('should fail when keyword is not in title', () => {
        const result = analyzeSEO({
          title: 'Complete Guide',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const titleCheck = result.results.find(r => r.id === 'keyword-in-title');
        expect(titleCheck).toBeDefined();
        expect(titleCheck?.type).toBe('problem');
        expect(titleCheck?.message).toBe('Focus keyword missing from SEO title');
      });

      it('should be case-insensitive', () => {
        const result = analyzeSEO({
          title: 'WORDPRESS guide',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const titleCheck = result.results.find(r => r.id === 'keyword-in-title');
        expect(titleCheck?.type).toBe('good');
      });
    });

    describe('Check 2: Keyword in description', () => {
      it('should pass when keyword is in description', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Learn about WordPress development',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const descCheck = result.results.find(r => r.id === 'keyword-in-description');
        expect(descCheck).toBeDefined();
        expect(descCheck?.type).toBe('good');
        expect(descCheck?.message).toBe('Focus keyword appears in meta description');
      });

      it('should fail when keyword is not in description', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Learn about development',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const descCheck = result.results.find(r => r.id === 'keyword-in-description');
        expect(descCheck).toBeDefined();
        expect(descCheck?.type).toBe('problem');
      });
    });

    describe('Check 3: Keyword in first paragraph', () => {
      it('should pass when keyword is in first paragraph', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<p>WordPress is a great CMS.</p><p>Second paragraph.</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const firstParaCheck = result.results.find(r => r.id === 'keyword-in-first-paragraph');
        expect(firstParaCheck).toBeDefined();
        expect(firstParaCheck?.type).toBe('good');
        expect(firstParaCheck?.message).toBe('Focus keyword appears in first paragraph');
      });

      it('should fail when keyword is not in first paragraph', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<p>This is the first paragraph.</p><p>WordPress is in the second.</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const firstParaCheck = result.results.find(r => r.id === 'keyword-in-first-paragraph');
        expect(firstParaCheck).toBeDefined();
        expect(firstParaCheck?.type).toBe('problem');
      });

      it('should handle content without paragraph tags', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: 'WordPress content without tags',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const firstParaCheck = result.results.find(r => r.id === 'keyword-in-first-paragraph');
        expect(firstParaCheck?.type).toBe('good');
      });
    });

    describe('Check 4: Keyword in headings', () => {
      it('should pass when keyword is in h1', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<h1>WordPress Guide</h1><p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const headingCheck = result.results.find(r => r.id === 'keyword-in-headings');
        expect(headingCheck).toBeDefined();
        expect(headingCheck?.type).toBe('good');
        expect(headingCheck?.message).toBe('Focus keyword appears in at least one heading');
      });

      it('should pass when keyword is in h2', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<h2>WordPress Tips</h2><p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const headingCheck = result.results.find(r => r.id === 'keyword-in-headings');
        expect(headingCheck?.type).toBe('good');
      });

      it('should pass when keyword is in any heading level (h1-h6)', () => {
        const headingLevels = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        
        headingLevels.forEach(level => {
          const result = analyzeSEO({
            title: 'Title',
            description: 'Description',
            content: `<${level}>WordPress Guide</${level}><p>Content</p>`,
            slug: 'slug',
            focusKeyword: 'wordpress',
          });

          const headingCheck = result.results.find(r => r.id === 'keyword-in-headings');
          expect(headingCheck?.type).toBe('good');
        });
      });

      it('should fail when keyword is not in any heading', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<h1>Guide</h1><h2>Tips</h2><p>WordPress content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const headingCheck = result.results.find(r => r.id === 'keyword-in-headings');
        expect(headingCheck).toBeDefined();
        expect(headingCheck?.type).toBe('problem');
      });

      it('should handle headings with nested HTML', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<h2><strong>WordPress</strong> Guide</h2><p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        const headingCheck = result.results.find(r => r.id === 'keyword-in-headings');
        expect(headingCheck?.type).toBe('good');
      });
    });

    describe('Check 5: Keyword in slug', () => {
      it('should pass when keyword is in slug', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'wordpress-guide',
          focusKeyword: 'wordpress',
        });

        const slugCheck = result.results.find(r => r.id === 'keyword-in-slug');
        expect(slugCheck).toBeDefined();
        expect(slugCheck?.type).toBe('good');
        expect(slugCheck?.message).toBe('Focus keyword appears in URL slug');
      });

      it('should fail when keyword is not in slug', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'complete-guide',
          focusKeyword: 'wordpress',
        });

        const slugCheck = result.results.find(r => r.id === 'keyword-in-slug');
        expect(slugCheck).toBeDefined();
        expect(slugCheck?.type).toBe('problem');
      });

      it('should handle multi-word keywords with spaces', () => {
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'wordpress-seo-guide',
          focusKeyword: 'wordpress seo',
        });

        const slugCheck = result.results.find(r => r.id === 'keyword-in-slug');
        expect(slugCheck?.type).toBe('good');
      });
    });

    describe('Score calculation', () => {
      it('should calculate score as 20 points per passed check', () => {
        // 1 check passed
        let result = analyzeSEO({
          title: 'WordPress',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });
        expect(result.score).toBe(20);

        // 2 checks passed
        result = analyzeSEO({
          title: 'WordPress',
          description: 'WordPress description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });
        expect(result.score).toBe(40);

        // 3 checks passed
        result = analyzeSEO({
          title: 'WordPress',
          description: 'WordPress description',
          content: '<p>WordPress content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });
        expect(result.score).toBe(60);
      });

      it('should never exceed 100', () => {
        const result = analyzeSEO({
          title: 'WordPress WordPress WordPress',
          description: 'WordPress WordPress WordPress',
          content: '<h1>WordPress</h1><p>WordPress</p>',
          slug: 'wordpress-wordpress',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBe(100);
      });
    });

    describe('Color determination', () => {
      it('should return red for scores < 40', () => {
        const result = analyzeSEO({
          title: 'WordPress',
          description: 'Description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBe(20);
        expect(result.color).toBe('red');
      });

      it('should return orange for scores 40-69', () => {
        const result = analyzeSEO({
          title: 'WordPress',
          description: 'WordPress description',
          content: '<p>Content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBe(40);
        expect(result.color).toBe('orange');
      });

      it('should return green for scores >= 70', () => {
        const result = analyzeSEO({
          title: 'WordPress',
          description: 'WordPress description',
          content: '<h1>WordPress</h1><p>WordPress content</p>',
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBe(80);
        expect(result.color).toBe('green');
      });
    });

    describe('Edge cases', () => {
      it('should handle empty strings', () => {
        const result = analyzeSEO({
          title: '',
          description: '',
          content: '',
          slug: '',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBe(0);
        expect(result.results).toHaveLength(5);
        expect(result.results.every(r => r.type === 'problem')).toBe(true);
      });

      it('should handle special characters in keyword', () => {
        const result = analyzeSEO({
          title: 'C++ Programming',
          description: 'Learn C++ programming',
          content: '<p>C++ is great</p>',
          slug: 'c++-programming',
          focusKeyword: 'c++',
        });

        expect(result.score).toBeGreaterThan(0);
      });

      it('should handle very long content', () => {
        const longContent = '<p>' + 'WordPress '.repeat(1000) + '</p>';
        const result = analyzeSEO({
          title: 'Title',
          description: 'Description',
          content: longContent,
          slug: 'slug',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBeGreaterThanOrEqual(0);
        expect(result.score).toBeLessThanOrEqual(100);
      });

      it('should handle content with no HTML tags', () => {
        const result = analyzeSEO({
          title: 'WordPress Guide',
          description: 'WordPress description',
          content: 'WordPress is a content management system',
          slug: 'wordpress-guide',
          focusKeyword: 'wordpress',
        });

        expect(result.score).toBeGreaterThan(0);
      });
    });
  });
});
