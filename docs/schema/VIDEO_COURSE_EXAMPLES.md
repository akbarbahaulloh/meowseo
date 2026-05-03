# Video & Course Schema Examples

Complete examples for the newly implemented Video and Course schema types.

---

## 🎥 Video Schema Examples

### Example 1: YouTube Tutorial Video

**Input Data:**
```php
$video_data = array(
    'type' => 'VideoObject',
    'data' => array(
        'name' => 'Complete WordPress Plugin Development Tutorial',
        'description' => 'Learn how to build a WordPress plugin from scratch. This comprehensive tutorial covers everything from basic setup to advanced features.',
        'thumbnailUrl' => 'https://example.com/wp-content/uploads/2026/05/plugin-tutorial-thumb.jpg',
        'uploadDate' => '2026-05-01T10:00:00+00:00',
        'duration' => 'PT1H45M30S',
        'embedUrl' => 'https://www.youtube.com/embed/abc123xyz',
        'contentUrl' => 'https://www.youtube.com/watch?v=abc123xyz',
        'author' => array(
            '@type' => 'Person',
            'name' => 'John Developer',
            'url' => 'https://example.com/author/john',
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => 'Code Academy',
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => 'https://example.com/logo.png',
            ),
        ),
        'interactionStatistic' => array(
            '@type' => 'InteractionCounter',
            'interactionType' => 'https://schema.org/WatchAction',
            'userInteractionCount' => 15420,
        ),
        'hasPart' => array(
            array(
                '@type' => 'Clip',
                'name' => 'Introduction & Setup',
                'startOffset' => 0,
                'endOffset' => 300,
                'url' => 'https://www.youtube.com/watch?v=abc123xyz&t=0s',
            ),
            array(
                '@type' => 'Clip',
                'name' => 'Creating Plugin Structure',
                'startOffset' => 300,
                'endOffset' => 900,
                'url' => 'https://www.youtube.com/watch?v=abc123xyz&t=300s',
            ),
            array(
                '@type' => 'Clip',
                'name' => 'Adding Admin Menu',
                'startOffset' => 900,
                'endOffset' => 1800,
                'url' => 'https://www.youtube.com/watch?v=abc123xyz&t=900s',
            ),
            array(
                '@type' => 'Clip',
                'name' => 'Database Integration',
                'startOffset' => 1800,
                'endOffset' => 3600,
                'url' => 'https://www.youtube.com/watch?v=abc123xyz&t=1800s',
            ),
            array(
                '@type' => 'Clip',
                'name' => 'Frontend Display',
                'startOffset' => 3600,
                'endOffset' => 5400,
                'url' => 'https://www.youtube.com/watch?v=abc123xyz&t=3600s',
            ),
            array(
                '@type' => 'Clip',
                'name' => 'Testing & Deployment',
                'startOffset' => 5400,
                'endOffset' => 6330,
                'url' => 'https://www.youtube.com/watch?v=abc123xyz&t=5400s',
            ),
        ),
    ),
);
```

**JSON-LD Output:**
```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Complete WordPress Plugin Development Tutorial",
  "description": "Learn how to build a WordPress plugin from scratch. This comprehensive tutorial covers everything from basic setup to advanced features.",
  "thumbnailUrl": "https://example.com/wp-content/uploads/2026/05/plugin-tutorial-thumb.jpg",
  "uploadDate": "2026-05-01T10:00:00+00:00",
  "duration": "PT1H45M30S",
  "embedUrl": "https://www.youtube.com/embed/abc123xyz",
  "contentUrl": "https://www.youtube.com/watch?v=abc123xyz",
  "author": {
    "@type": "Person",
    "name": "John Developer",
    "url": "https://example.com/author/john"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Code Academy",
    "logo": {
      "@type": "ImageObject",
      "url": "https://example.com/logo.png"
    }
  },
  "interactionStatistic": {
    "@type": "InteractionCounter",
    "interactionType": "https://schema.org/WatchAction",
    "userInteractionCount": 15420
  },
  "hasPart": [
    {
      "@type": "Clip",
      "name": "Introduction & Setup",
      "startOffset": 0,
      "endOffset": 300,
      "url": "https://www.youtube.com/watch?v=abc123xyz&t=0s"
    },
    {
      "@type": "Clip",
      "name": "Creating Plugin Structure",
      "startOffset": 300,
      "endOffset": 900,
      "url": "https://www.youtube.com/watch?v=abc123xyz&t=300s"
    },
    {
      "@type": "Clip",
      "name": "Adding Admin Menu",
      "startOffset": 900,
      "endOffset": 1800,
      "url": "https://www.youtube.com/watch?v=abc123xyz&t=900s"
    },
    {
      "@type": "Clip",
      "name": "Database Integration",
      "startOffset": 1800,
      "endOffset": 3600,
      "url": "https://www.youtube.com/watch?v=abc123xyz&t=1800s"
    },
    {
      "@type": "Clip",
      "name": "Frontend Display",
      "startOffset": 3600,
      "endOffset": 5400,
      "url": "https://www.youtube.com/watch?v=abc123xyz&t=3600s"
    },
    {
      "@type": "Clip",
      "name": "Testing & Deployment",
      "startOffset": 5400,
      "endOffset": 6330,
      "url": "https://www.youtube.com/watch?v=abc123xyz&t=5400s"
    }
  ]
}
```

**Benefits:**
- ✅ Video appears in Google Video Search
- ✅ Key moments show in search results
- ✅ View count displayed
- ✅ Rich snippets with thumbnail
- ✅ Seekable video timeline

---

### Example 2: Self-Hosted Product Demo

**Input Data:**
```php
$video_data = array(
    'type' => 'VideoObject',
    'data' => array(
        'name' => 'MeowSEO Plugin Demo - Complete Walkthrough',
        'description' => 'See MeowSEO in action! This demo shows all features including meta optimization, schema generator, and analytics.',
        'thumbnailUrl' => '%featured_image%',
        'uploadDate' => '%date(Y-m-d\TH:i:sP)%',
        'duration' => 'PT12M45S',
        'contentUrl' => 'https://example.com/videos/meowseo-demo.mp4',
        'embedUrl' => 'https://example.com/video-player/meowseo-demo',
        'transcript' => 'Welcome to MeowSEO! In this demo, I will show you how to optimize your WordPress site for search engines...',
        'author' => array(
            '@type' => 'Organization',
            'name' => '%sitename%',
            'url' => '%siteurl%',
        ),
    ),
);
```

**Use Case:** Product demos, feature walkthroughs, self-hosted videos

---

## 🎓 Course Schema Examples

### Example 1: Complete Online Course

**Input Data:**
```php
$course_data = array(
    'type' => 'Course',
    'data' => array(
        'name' => 'Complete Web Development Bootcamp 2026',
        'description' => 'Master web development from zero to hero. Learn HTML, CSS, JavaScript, React, Node.js, and deploy real-world projects.',
        'image' => 'https://example.com/courses/web-dev-bootcamp.jpg',
        'provider' => array(
            '@type' => 'Organization',
            'name' => 'Code Academy Pro',
            'url' => 'https://example.com',
        ),
        'courseCode' => 'WEB-2026-001',
        'educationalLevel' => 'Beginner',
        'hasCourseInstance' => array(
            array(
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'courseSchedule' => array(
                    '@type' => 'Schedule',
                    'startDate' => '2026-06-01',
                    'endDate' => '2026-08-31',
                    'duration' => 'P12W',
                ),
                'instructor' => array(
                    '@type' => 'Person',
                    'name' => 'Sarah Johnson',
                    'url' => 'https://example.com/instructors/sarah-johnson',
                ),
                'location' => array(
                    '@type' => 'VirtualLocation',
                    'url' => 'https://example.com/classroom/web-dev-2026',
                ),
            ),
            array(
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'courseSchedule' => array(
                    '@type' => 'Schedule',
                    'startDate' => '2026-09-01',
                    'endDate' => '2026-11-30',
                    'duration' => 'P12W',
                ),
                'instructor' => array(
                    '@type' => 'Person',
                    'name' => 'Michael Chen',
                    'url' => 'https://example.com/instructors/michael-chen',
                ),
                'location' => array(
                    '@type' => 'VirtualLocation',
                    'url' => 'https://example.com/classroom/web-dev-2026-fall',
                ),
            ),
        ),
        'offers' => array(
            '@type' => 'Offer',
            'price' => 499,
            'priceCurrency' => 'USD',
            'category' => 'Paid',
            'availability' => 'https://schema.org/InStock',
            'url' => 'https://example.com/courses/web-dev-bootcamp/enroll',
        ),
        'aggregateRating' => array(
            '@type' => 'AggregateRating',
            'ratingValue' => 4.8,
            'bestRating' => 5,
            'worstRating' => 1,
            'ratingCount' => 1247,
            'reviewCount' => 892,
        ),
        'timeRequired' => 'P12W',
        'inLanguage' => 'en',
        'teaches' => "HTML5 & CSS3 Fundamentals\nJavaScript ES6+ Programming\nReact.js Framework\nNode.js & Express Backend\nMongoDB Database\nRESTful API Development\nGit & GitHub Workflow\nDeployment & DevOps Basics",
        'coursePrerequisites' => "Basic computer skills\nInternet connection\nWillingness to learn\nNo prior coding experience required",
    ),
);
```

**JSON-LD Output:**
```json
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": "Complete Web Development Bootcamp 2026",
  "description": "Master web development from zero to hero. Learn HTML, CSS, JavaScript, React, Node.js, and deploy real-world projects.",
  "image": "https://example.com/courses/web-dev-bootcamp.jpg",
  "provider": {
    "@type": "Organization",
    "name": "Code Academy Pro",
    "url": "https://example.com"
  },
  "courseCode": "WEB-2026-001",
  "educationalLevel": "Beginner",
  "hasCourseInstance": [
    {
      "@type": "CourseInstance",
      "courseMode": "online",
      "courseSchedule": {
        "@type": "Schedule",
        "startDate": "2026-06-01",
        "endDate": "2026-08-31",
        "duration": "P12W"
      },
      "instructor": {
        "@type": "Person",
        "name": "Sarah Johnson",
        "url": "https://example.com/instructors/sarah-johnson"
      },
      "location": {
        "@type": "VirtualLocation",
        "url": "https://example.com/classroom/web-dev-2026"
      }
    },
    {
      "@type": "CourseInstance",
      "courseMode": "online",
      "courseSchedule": {
        "@type": "Schedule",
        "startDate": "2026-09-01",
        "endDate": "2026-11-30",
        "duration": "P12W"
      },
      "instructor": {
        "@type": "Person",
        "name": "Michael Chen",
        "url": "https://example.com/instructors/michael-chen"
      },
      "location": {
        "@type": "VirtualLocation",
        "url": "https://example.com/classroom/web-dev-2026-fall"
      }
    }
  ],
  "offers": {
    "@type": "Offer",
    "price": 499,
    "priceCurrency": "USD",
    "category": "Paid",
    "availability": "https://schema.org/InStock",
    "url": "https://example.com/courses/web-dev-bootcamp/enroll"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": 4.8,
    "bestRating": 5,
    "worstRating": 1,
    "ratingCount": 1247,
    "reviewCount": 892
  },
  "timeRequired": "P12W",
  "inLanguage": "en",
  "teaches": [
    "HTML5 & CSS3 Fundamentals",
    "JavaScript ES6+ Programming",
    "React.js Framework",
    "Node.js & Express Backend",
    "MongoDB Database",
    "RESTful API Development",
    "Git & GitHub Workflow",
    "Deployment & DevOps Basics"
  ],
  "coursePrerequisites": [
    "Basic computer skills",
    "Internet connection",
    "Willingness to learn",
    "No prior coding experience required"
  ]
}
```

**Benefits:**
- ✅ Course appears in Google Course Search
- ✅ Multiple instances shown
- ✅ Pricing displayed
- ✅ Ratings visible
- ✅ Learning outcomes listed
- ✅ Prerequisites clear

---

### Example 2: Free University Course

**Input Data:**
```php
$course_data = array(
    'type' => 'Course',
    'data' => array(
        'name' => 'Introduction to Computer Science',
        'description' => 'Learn the fundamentals of computer science including algorithms, data structures, and programming concepts.',
        'image' => '%featured_image%',
        'provider' => array(
            '@type' => 'Organization',
            'name' => 'State University',
            'url' => 'https://university.edu',
        ),
        'courseCode' => 'CS101',
        'educationalLevel' => 'Beginner',
        'hasCourseInstance' => array(
            array(
                '@type' => 'CourseInstance',
                'courseMode' => 'blended',
                'courseSchedule' => array(
                    '@type' => 'Schedule',
                    'startDate' => '2026-09-01',
                    'endDate' => '2026-12-15',
                    'duration' => 'P15W',
                ),
                'instructor' => array(
                    '@type' => 'Person',
                    'name' => 'Dr. Robert Smith',
                    'url' => 'https://university.edu/faculty/robert-smith',
                ),
                'location' => array(
                    '@type' => 'Place',
                    'name' => 'Science Building Room 101',
                    'address' => '123 University Ave, City, State 12345',
                ),
            ),
        ),
        'offers' => array(
            '@type' => 'Offer',
            'price' => 0,
            'priceCurrency' => 'USD',
            'category' => 'Free',
            'availability' => 'https://schema.org/InStock',
            'url' => '%permalink%',
        ),
        'timeRequired' => 'P15W',
        'inLanguage' => 'en',
        'teaches' => "Programming fundamentals\nAlgorithms and complexity\nData structures\nProblem-solving techniques",
        'coursePrerequisites' => "High school mathematics\nBasic algebra",
    ),
);
```

**Use Case:** University courses, free MOOCs, academic programs

---

### Example 3: Professional Certification

**Input Data:**
```php
$course_data = array(
    'type' => 'Course',
    'data' => array(
        'name' => 'AWS Certified Solutions Architect Preparation',
        'description' => 'Comprehensive preparation course for AWS Solutions Architect certification exam.',
        'provider' => array(
            '@type' => 'Organization',
            'name' => '%sitename%',
            'url' => '%siteurl%',
        ),
        'courseCode' => 'AWS-SAA-C03',
        'educationalLevel' => 'Advanced',
        'hasCourseInstance' => array(
            array(
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'courseSchedule' => array(
                    '@type' => 'Schedule',
                    'duration' => 'P8W',
                ),
                'location' => array(
                    '@type' => 'VirtualLocation',
                    'url' => '%permalink%',
                ),
            ),
        ),
        'offers' => array(
            '@type' => 'Offer',
            'price' => 199,
            'priceCurrency' => 'USD',
            'category' => 'Paid',
            'availability' => 'https://schema.org/InStock',
            'url' => '%permalink%',
        ),
        'timeRequired' => 'P8W',
        'inLanguage' => 'en',
        'teaches' => "AWS Core Services\nArchitecture Best Practices\nSecurity & Compliance\nCost Optimization\nHigh Availability Design",
        'coursePrerequisites' => "1+ years AWS experience\nBasic networking knowledge\nLinux/Windows administration",
    ),
);
```

**Use Case:** Certification prep, professional training, skill development

---

## 🎯 Use Cases Summary

### Video Schema Perfect For:
- ✅ YouTube/Vimeo videos
- ✅ Product demos
- ✅ Tutorial videos
- ✅ Webinar recordings
- ✅ Video courses
- ✅ How-to videos
- ✅ Video reviews
- ✅ Self-hosted videos

### Course Schema Perfect For:
- ✅ Online courses (Udemy-style)
- ✅ University courses
- ✅ Training programs
- ✅ Certification prep
- ✅ Bootcamps
- ✅ Workshops
- ✅ MOOCs
- ✅ Professional development

---

## 💡 Pro Tips

### Video Schema Tips:
1. **Use Key Moments** - Add hasPart segments for better UX
2. **Include Transcript** - Helps with accessibility and SEO
3. **Track Views** - Add interactionStatistic for social proof
4. **Proper Duration** - Use ISO 8601 format (PT1H30M)
5. **High-Quality Thumbnail** - Use at least 1280x720px

### Course Schema Tips:
1. **Multiple Instances** - Show different start dates
2. **Clear Prerequisites** - Help students self-assess
3. **Learning Outcomes** - List what students will learn
4. **Accurate Pricing** - Include currency and category
5. **Add Ratings** - Social proof increases enrollment
6. **Time Commitment** - Be clear about duration

---

## 📊 SEO Benefits

### Video Schema:
- 📈 Appears in Google Video Search
- 📈 Rich snippets with thumbnail
- 📈 Key moments in search results
- 📈 Higher click-through rates
- 📈 Better video discovery

### Course Schema:
- 📈 Appears in Google Course Search
- 📈 Rich snippets with pricing
- 📈 Multiple instances shown
- 📈 Ratings displayed
- 📈 Better course discovery
- 📈 Higher enrollment rates

---

**Both schemas are production-ready and fully functional!** 🎉

