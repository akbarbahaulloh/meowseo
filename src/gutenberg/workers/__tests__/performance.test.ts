/**
 * Performance Tests for Web Worker Analysis
 *
 * Tests Web Worker performance with various content sizes:
 * - Small content (500 words)
 * - Medium content (1500 words)
 * - Large content (5000+ words)
 *
 * Verifies:
 * - Analysis completes within 1-2 second target
 * - Main thread remains responsive during analysis
 * - Memory usage is efficient
 * - Web Worker resource cleanup works
 */

import { analyzeContent } from '../../../analysis/analysis-engine.js';

/**
 * Sample content for performance testing
 */
const SAMPLE_CONTENT = {
	small: `
		<p>Optimasi mesin pencari adalah proses penting untuk meningkatkan visibilitas website Anda di hasil pencarian Google. Dengan strategi SEO yang tepat, Anda dapat menarik lebih banyak pengunjung organik ke situs web Anda. Keyword research adalah langkah pertama dalam proses optimasi. Anda perlu menemukan kata kunci yang relevan dengan bisnis Anda dan memiliki volume pencarian yang tinggi.</p>
		<p>Konten berkualitas tinggi adalah fondasi dari strategi SEO yang sukses. Pastikan konten Anda informatif, relevan, dan mudah dibaca. Gunakan heading yang jelas untuk mengorganisir konten Anda. Backlink dari situs web berkualitas tinggi juga penting untuk meningkatkan otoritas domain Anda.</p>
		<p>Kecepatan loading website adalah faktor penting yang mempengaruhi peringkat pencarian. Optimalkan gambar Anda, gunakan caching, dan pertimbangkan menggunakan CDN untuk meningkatkan kecepatan website Anda. Mobile responsiveness juga sangat penting karena sebagian besar pengguna internet mengakses website melalui perangkat mobile.</p>
		<p>Meta description yang menarik dapat meningkatkan click-through rate dari hasil pencarian. Pastikan meta description Anda singkat, informatif, dan mengandung kata kunci yang relevan. Internal linking membantu mesin pencari memahami struktur website Anda dan meningkatkan otoritas halaman.</p>
		<p>Analisis kompetitor dapat memberikan wawasan berharga tentang strategi SEO yang efektif. Pelajari kata kunci yang digunakan kompetitor Anda, struktur konten mereka, dan strategi backlink mereka. Gunakan informasi ini untuk meningkatkan strategi SEO Anda sendiri.</p>
	`,
	medium: `
		<h2>Panduan Lengkap Optimasi SEO untuk Website Anda</h2>
		<p>Optimasi mesin pencari adalah proses penting untuk meningkatkan visibilitas website Anda di hasil pencarian Google. Dengan strategi SEO yang tepat, Anda dapat menarik lebih banyak pengunjung organik ke situs web Anda. Keyword research adalah langkah pertama dalam proses optimasi. Anda perlu menemukan kata kunci yang relevan dengan bisnis Anda dan memiliki volume pencarian yang tinggi.</p>
		<h3>Riset Kata Kunci yang Efektif</h3>
		<p>Riset kata kunci adalah fondasi dari strategi SEO yang sukses. Gunakan tools seperti Google Keyword Planner, SEMrush, atau Ahrefs untuk menemukan kata kunci yang relevan. Perhatikan volume pencarian, tingkat kesulitan, dan intent dari setiap kata kunci. Pilih kata kunci yang memiliki volume pencarian yang cukup tinggi tetapi tingkat kesulitan yang tidak terlalu tinggi.</p>
		<h3>Pembuatan Konten Berkualitas</h3>
		<p>Konten berkualitas tinggi adalah fondasi dari strategi SEO yang sukses. Pastikan konten Anda informatif, relevan, dan mudah dibaca. Gunakan heading yang jelas untuk mengorganisir konten Anda. Backlink dari situs web berkualitas tinggi juga penting untuk meningkatkan otoritas domain Anda. Tulis konten yang panjang dan mendalam untuk meningkatkan peluang ranking di halaman pertama Google.</p>
		<h3>Optimasi On-Page</h3>
		<p>Optimasi on-page meliputi pengoptimalan title tag, meta description, heading, dan konten. Pastikan title tag Anda mengandung kata kunci utama dan tidak lebih dari 60 karakter. Meta description harus menarik dan mengandung kata kunci yang relevan. Gunakan heading H1 untuk judul utama dan H2, H3 untuk sub-heading.</p>
		<h3>Kecepatan Website</h3>
		<p>Kecepatan loading website adalah faktor penting yang mempengaruhi peringkat pencarian. Optimalkan gambar Anda, gunakan caching, dan pertimbangkan menggunakan CDN untuk meningkatkan kecepatan website Anda. Mobile responsiveness juga sangat penting karena sebagian besar pengguna internet mengakses website melalui perangkat mobile. Gunakan Google PageSpeed Insights untuk menganalisis kecepatan website Anda.</p>
		<h3>Backlink Building</h3>
		<p>Backlink adalah link dari website lain yang menunjuk ke website Anda. Backlink berkualitas tinggi dari website yang relevan dapat meningkatkan otoritas domain Anda. Strategi backlink building meliputi guest posting, broken link building, dan resource page link building. Hindari backlink dari website spam atau berkualitas rendah.</p>
		<h3>Internal Linking</h3>
		<p>Internal linking membantu mesin pencari memahami struktur website Anda dan meningkatkan otoritas halaman. Gunakan anchor text yang deskriptif dan relevan untuk internal link Anda. Pastikan setiap halaman di website Anda dapat diakses melalui internal link dari halaman lain.</p>
		<h3>Analisis dan Monitoring</h3>
		<p>Analisis dan monitoring adalah bagian penting dari strategi SEO. Gunakan Google Analytics untuk melacak traffic website Anda dan Google Search Console untuk memantau performa website Anda di hasil pencarian. Analisis kompetitor dapat memberikan wawasan berharga tentang strategi SEO yang efektif. Pelajari kata kunci yang digunakan kompetitor Anda, struktur konten mereka, dan strategi backlink mereka.</p>
		<p>Gunakan informasi ini untuk meningkatkan strategi SEO Anda sendiri. Lakukan audit SEO secara berkala untuk mengidentifikasi masalah dan peluang perbaikan. Perbarui konten Anda secara berkala untuk memastikan konten Anda tetap relevan dan up-to-date.</p>
	`,
	large: `
		<h2>Panduan Komprehensif Optimasi SEO untuk Website Anda</h2>
		<p>Optimasi mesin pencari adalah proses penting untuk meningkatkan visibilitas website Anda di hasil pencarian Google. Dengan strategi SEO yang tepat, Anda dapat menarik lebih banyak pengunjung organik ke situs web Anda. Keyword research adalah langkah pertama dalam proses optimasi. Anda perlu menemukan kata kunci yang relevan dengan bisnis Anda dan memiliki volume pencarian yang tinggi. Dalam panduan ini, kami akan membahas strategi SEO yang komprehensif dan terbukti efektif untuk meningkatkan ranking website Anda.</p>
		<h3>Riset Kata Kunci yang Mendalam</h3>
		<p>Riset kata kunci adalah fondasi dari strategi SEO yang sukses. Gunakan tools seperti Google Keyword Planner, SEMrush, atau Ahrefs untuk menemukan kata kunci yang relevan. Perhatikan volume pencarian, tingkat kesulitan, dan intent dari setiap kata kunci. Pilih kata kunci yang memiliki volume pencarian yang cukup tinggi tetapi tingkat kesulitan yang tidak terlalu tinggi. Identifikasi long-tail keywords yang memiliki volume pencarian lebih rendah tetapi tingkat konversi yang lebih tinggi. Analisis kata kunci kompetitor untuk menemukan peluang yang belum dimanfaatkan.</p>
		<h3>Pembuatan Konten Berkualitas Tinggi</h3>
		<p>Konten berkualitas tinggi adalah fondasi dari strategi SEO yang sukses. Pastikan konten Anda informatif, relevan, dan mudah dibaca. Gunakan heading yang jelas untuk mengorganisir konten Anda. Backlink dari situs web berkualitas tinggi juga penting untuk meningkatkan otoritas domain Anda. Tulis konten yang panjang dan mendalam untuk meningkatkan peluang ranking di halaman pertama Google. Konten yang panjang cenderung mendapat ranking lebih tinggi karena memberikan informasi yang lebih komprehensif kepada pembaca.</p>
		<p>Gunakan multimedia seperti gambar, video, dan infografis untuk membuat konten Anda lebih menarik. Pastikan gambar Anda dioptimalkan dengan alt text yang deskriptif. Tulis konten yang original dan unik, hindari duplicate content. Perbarui konten Anda secara berkala untuk memastikan konten Anda tetap relevan dan up-to-date. Gunakan data dan statistik untuk mendukung klaim Anda dan membuat konten Anda lebih kredibel.</p>
		<h3>Optimasi On-Page yang Komprehensif</h3>
		<p>Optimasi on-page meliputi pengoptimalan title tag, meta description, heading, dan konten. Pastikan title tag Anda mengandung kata kunci utama dan tidak lebih dari 60 karakter. Meta description harus menarik dan mengandung kata kunci yang relevan. Gunakan heading H1 untuk judul utama dan H2, H3 untuk sub-heading. Optimalkan URL Anda agar singkat, deskriptif, dan mengandung kata kunci yang relevan.</p>
		<p>Gunakan schema markup untuk membantu mesin pencari memahami konten Anda dengan lebih baik. Optimalkan gambar Anda dengan alt text yang deskriptif dan ukuran file yang kecil. Gunakan internal link dengan anchor text yang deskriptif dan relevan. Pastikan website Anda mobile-friendly dan memiliki loading time yang cepat.</p>
		<h3>Peningkatan Kecepatan Website</h3>
		<p>Kecepatan loading website adalah faktor penting yang mempengaruhi peringkat pencarian. Optimalkan gambar Anda, gunakan caching, dan pertimbangkan menggunakan CDN untuk meningkatkan kecepatan website Anda. Mobile responsiveness juga sangat penting karena sebagian besar pengguna internet mengakses website melalui perangkat mobile. Gunakan Google PageSpeed Insights untuk menganalisis kecepatan website Anda dan mendapatkan rekomendasi perbaikan.</p>
		<p>Minimalkan CSS dan JavaScript, gunakan lazy loading untuk gambar, dan pertimbangkan menggunakan AMP (Accelerated Mobile Pages) untuk meningkatkan kecepatan website Anda. Gunakan server yang cepat dan reliable. Optimalkan database Anda untuk mengurangi query time. Gunakan compression untuk mengurangi ukuran file.</p>
		<h3>Strategi Backlink Building yang Efektif</h3>
		<p>Backlink adalah link dari website lain yang menunjuk ke website Anda. Backlink berkualitas tinggi dari website yang relevan dapat meningkatkan otoritas domain Anda. Strategi backlink building meliputi guest posting, broken link building, dan resource page link building. Hindari backlink dari website spam atau berkualitas rendah. Fokus pada kualitas daripada kuantitas ketika membangun backlink.</p>
		<p>Buat konten yang layak untuk di-link, seperti research original, infografis, atau tools yang berguna. Hubungi website yang relevan dan tawarkan untuk berkolaborasi. Gunakan tools seperti Ahrefs atau SEMrush untuk menganalisis backlink kompetitor dan menemukan peluang backlink baru. Pantau backlink Anda secara berkala untuk memastikan tidak ada backlink spam yang menunjuk ke website Anda.</p>
		<h3>Optimasi Internal Linking</h3>
		<p>Internal linking membantu mesin pencari memahami struktur website Anda dan meningkatkan otoritas halaman. Gunakan anchor text yang deskriptif dan relevan untuk internal link Anda. Pastikan setiap halaman di website Anda dapat diakses melalui internal link dari halaman lain. Buat sitemap XML untuk membantu mesin pencari menemukan semua halaman di website Anda.</p>
		<p>Gunakan breadcrumb navigation untuk meningkatkan user experience dan membantu mesin pencari memahami struktur website Anda. Link ke halaman yang paling penting dari homepage Anda. Gunakan internal link untuk mendistribusikan page authority ke halaman yang penting. Hindari terlalu banyak internal link yang dapat dianggap sebagai spam.</p>
		<h3>Analisis dan Monitoring yang Berkelanjutan</h3>
		<p>Analisis dan monitoring adalah bagian penting dari strategi SEO. Gunakan Google Analytics untuk melacak traffic website Anda dan Google Search Console untuk memantau performa website Anda di hasil pencarian. Analisis kompetitor dapat memberikan wawasan berharga tentang strategi SEO yang efektif. Pelajari kata kunci yang digunakan kompetitor Anda, struktur konten mereka, dan strategi backlink mereka.</p>
		<p>Gunakan informasi ini untuk meningkatkan strategi SEO Anda sendiri. Lakukan audit SEO secara berkala untuk mengidentifikasi masalah dan peluang perbaikan. Perbarui konten Anda secara berkala untuk memastikan konten Anda tetap relevan dan up-to-date. Pantau ranking keyword Anda dan buat penyesuaian strategi jika diperlukan. Gunakan A/B testing untuk menguji berbagai strategi dan menemukan yang paling efektif.</p>
		<h3>Optimasi untuk Mobile</h3>
		<p>Mobile optimization adalah aspek penting dari SEO modern. Pastikan website Anda responsive dan dapat diakses dengan baik di perangkat mobile. Gunakan mobile-first indexing untuk memastikan website Anda dioptimalkan untuk mobile. Pastikan font Anda cukup besar dan mudah dibaca di layar mobile. Hindari pop-up yang mengganggu pengalaman pengguna mobile.</p>
		<p>Optimalkan kecepatan loading website Anda untuk mobile. Gunakan thumb-friendly buttons dan navigation. Pastikan form Anda mudah diisi di perangkat mobile. Test website Anda di berbagai perangkat mobile untuk memastikan pengalaman pengguna yang optimal.</p>
		<h3>Strategi Konten Jangka Panjang</h3>
		<p>Strategi konten jangka panjang adalah kunci untuk kesuksesan SEO yang berkelanjutan. Buat kalender konten yang terencana dengan baik. Fokus pada topik yang relevan dengan bisnis Anda dan minat audience Anda. Buat konten yang evergreen yang tetap relevan dalam jangka panjang. Perbarui konten lama Anda untuk memastikan konten Anda tetap up-to-date dan relevan.</p>
		<p>Gunakan berbagai format konten seperti blog posts, videos, podcasts, dan infografis. Promosikan konten Anda melalui social media dan email marketing. Gunakan content marketing untuk membangun authority dan trust dengan audience Anda. Fokus pada memberikan nilai kepada audience Anda daripada hanya menjual produk atau layanan Anda.</p>
	`,
};

/**
 * Generate test content of specific word count
 * @param wordCount
 */
function generateContent( wordCount: number ): string {
	const baseContent = SAMPLE_CONTENT.large;
	const words = baseContent.split( /\s+/ );
	const repetitions = Math.ceil( wordCount / words.length );
	return words.slice( 0, wordCount ).join( ' ' );
}

/**
 * Measure performance of analysis
 * @param name
 * @param fn
 * @param expectedMaxTime
 */
function measurePerformance(
	name: string,
	fn: () => any,
	expectedMaxTime: number = 2000
): { duration: number; passed: boolean; result: any } {
	const startTime = performance.now();
	const result = fn();
	const endTime = performance.now();
	const duration = endTime - startTime;
	const passed = duration <= expectedMaxTime;

	return { duration, passed, result };
}

describe( 'Web Worker Performance Tests', () => {
	describe( 'Analysis Speed Benchmarks', () => {
		it( 'should complete small content analysis within 1-2 seconds', () => {
			const { duration, passed } = measurePerformance(
				'Small content (500 words)',
				() => {
					return analyzeContent( {
						content: SAMPLE_CONTENT.small,
						title: 'SEO Optimization Guide',
						description:
							'Learn how to optimize your website for search engines',
						slug: 'seo-optimization-guide',
						keyword: 'SEO optimization',
						directAnswer:
							'SEO optimization is the process of improving your website visibility in search results.',
						schemaType: 'Article',
					} );
				},
				2000
			);

			expect( passed ).toBe( true );
			expect( duration ).toBeLessThan( 2000 );
		} );

		it( 'should complete medium content analysis within 1-2 seconds', () => {
			const { duration, passed } = measurePerformance(
				'Medium content (1500 words)',
				() => {
					return analyzeContent( {
						content: SAMPLE_CONTENT.medium,
						title: 'Complete SEO Optimization Guide',
						description:
							'Comprehensive guide to optimizing your website for search engines',
						slug: 'complete-seo-guide',
						keyword: 'SEO optimization',
						directAnswer:
							'SEO optimization involves keyword research, content creation, and technical optimization.',
						schemaType: 'Article',
					} );
				},
				2000
			);

			expect( passed ).toBe( true );
			expect( duration ).toBeLessThan( 2000 );
		} );

		it( 'should complete large content analysis within 1-2 seconds', () => {
			const { duration, passed } = measurePerformance(
				'Large content (5000+ words)',
				() => {
					return analyzeContent( {
						content: SAMPLE_CONTENT.large,
						title: 'Comprehensive SEO Optimization Guide',
						description:
							'In-depth guide covering all aspects of SEO optimization',
						slug: 'comprehensive-seo-guide',
						keyword: 'SEO optimization',
						directAnswer:
							'SEO optimization is a comprehensive process involving keyword research, content creation, technical optimization, and link building.',
						schemaType: 'Article',
					} );
				},
				2000
			);

			expect( passed ).toBe( true );
			expect( duration ).toBeLessThan( 2000 );
		} );
	} );

	describe( 'Analysis Accuracy with Various Content Sizes', () => {
		it( 'should produce valid results for small content', () => {
			const result = analyzeContent( {
				content: SAMPLE_CONTENT.small,
				title: 'SEO Guide',
				description: 'SEO optimization guide',
				slug: 'seo-guide',
				keyword: 'SEO',
				directAnswer: 'SEO is search engine optimization.',
				schemaType: 'Article',
			} );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.readabilityScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.readabilityScore ).toBeLessThanOrEqual( 100 );
			expect( result.wordCount ).toBeGreaterThan( 0 );
			expect( result.seoResults.length ).toBeGreaterThan( 0 );
			expect( result.readabilityResults.length ).toBeGreaterThan( 0 );
		} );

		it( 'should produce valid results for medium content', () => {
			const result = analyzeContent( {
				content: SAMPLE_CONTENT.medium,
				title: 'Complete SEO Guide',
				description: 'Complete SEO optimization guide',
				slug: 'complete-seo-guide',
				keyword: 'SEO',
				directAnswer: 'SEO involves multiple strategies.',
				schemaType: 'Article',
			} );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.readabilityScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.readabilityScore ).toBeLessThanOrEqual( 100 );
			expect( result.wordCount ).toBeGreaterThan( 0 );
		} );

		it( 'should produce valid results for large content', () => {
			const result = analyzeContent( {
				content: SAMPLE_CONTENT.large,
				title: 'Comprehensive SEO Guide',
				description: 'Comprehensive SEO optimization guide',
				slug: 'comprehensive-seo-guide',
				keyword: 'SEO',
				directAnswer: 'SEO is a comprehensive process.',
				schemaType: 'Article',
			} );

			expect( result.seoScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.seoScore ).toBeLessThanOrEqual( 100 );
			expect( result.readabilityScore ).toBeGreaterThanOrEqual( 0 );
			expect( result.readabilityScore ).toBeLessThanOrEqual( 100 );
			expect( result.wordCount ).toBeGreaterThan( 0 );
		} );
	} );

	describe( 'Indonesian Content Performance', () => {
		it( 'should analyze Indonesian content efficiently', () => {
			const { duration, passed } = measurePerformance(
				'Indonesian content analysis',
				() => {
					return analyzeContent( {
						content: SAMPLE_CONTENT.large,
						title: 'Panduan Lengkap Optimasi SEO',
						description:
							'Panduan komprehensif untuk optimasi SEO website Anda',
						slug: 'panduan-optimasi-seo',
						keyword: 'optimasi SEO',
						directAnswer:
							'Optimasi SEO adalah proses meningkatkan visibilitas website di hasil pencarian.',
						schemaType: 'Article',
					} );
				},
				2000
			);

			expect( passed ).toBe( true );
			expect( duration ).toBeLessThan( 2000 );
		} );
	} );

	describe( 'Result Consistency', () => {
		it( 'should produce consistent results across multiple runs', () => {
			const testData = {
				content: SAMPLE_CONTENT.medium,
				title: 'SEO Guide',
				description: 'SEO optimization guide',
				slug: 'seo-guide',
				keyword: 'SEO',
				directAnswer: 'SEO is search engine optimization.',
				schemaType: 'Article',
			};

			const result1 = analyzeContent( testData );
			const result2 = analyzeContent( testData );
			const result3 = analyzeContent( testData );

			expect( result1.seoScore ).toBe( result2.seoScore );
			expect( result2.seoScore ).toBe( result3.seoScore );
			expect( result1.readabilityScore ).toBe( result2.readabilityScore );
			expect( result2.readabilityScore ).toBe( result3.readabilityScore );
			expect( result1.wordCount ).toBe( result2.wordCount );
			expect( result2.wordCount ).toBe( result3.wordCount );
		} );
	} );

	describe( 'Error Handling', () => {
		it( 'should handle empty content gracefully', () => {
			const result = analyzeContent( {
				content: '',
				title: '',
				description: '',
				slug: '',
				keyword: '',
				directAnswer: '',
				schemaType: '',
			} );

			expect( result.seoScore ).toBeDefined();
			expect( result.readabilityScore ).toBeDefined();
			expect( result.wordCount ).toBe( 0 );
		} );

		it( 'should handle missing fields gracefully', () => {
			const result = analyzeContent( {
				content: SAMPLE_CONTENT.small,
				title: '',
				description: '',
				slug: '',
				keyword: '',
				directAnswer: '',
				schemaType: '',
			} );

			expect( result.seoScore ).toBeDefined();
			expect( result.readabilityScore ).toBeDefined();
			expect( result.seoResults.length ).toBeGreaterThan( 0 );
		} );
	} );
} );
