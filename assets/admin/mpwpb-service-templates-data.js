/**
 * Static "quick add" service catalogue for the Pricing step's "Use Template"
 * picker (mpwpb-categories-services-modern.js). Every price/duration here is
 * a starting suggestion only -- the admin can edit or remove any field
 * before/after adding. Duration follows this plugin's existing free-text
 * convention (e.g. "30min", "1h", "1h30min"), same as the manual Add Service
 * modal's own Duration field.
 *
 * Shape: { "<business type label>": { icon, category?, items: [...] } }
 * - icon: a real dashicons class (shown on the picker's grid tile).
 * - category (optional): only set on a handful of business types where a
 *   real grouping category makes sense. When present, picking any of that
 *   type's services also creates (or reuses, if one with this exact name
 *   already exists) a matching category and assigns the added services to
 *   it -- shown on the tile as a small "Category: X" badge. Types without
 *   this field fall back to whatever category is currently selected in the
 *   sidebar (or none), same as the manual Add Service button.
 */
window.mpwpbServiceTemplates = {
	'Car Wash & Auto Detailing': {
		icon: 'dashicons-admin-tools',
		category: 'Car Wash & Detailing',
		items: [
			{ name: 'Basic Exterior Wash', price: 15, duration: '30min', desc: 'Exterior hand wash, wheel clean, and dry.' },
			{ name: 'Interior Vacuum & Wipe Down', price: 20, duration: '30min', desc: 'Vacuum seats/carpets and wipe dashboard & console.' },
			{ name: 'Full Detail (Interior + Exterior)', price: 80, duration: '2h', desc: 'Complete wash, vacuum, wax, and interior deep clean.' },
			{ name: 'Wax & Polish', price: 45, duration: '1h', desc: 'Hand wax and polish for a glossy protective finish.' },
			{ name: 'Engine Bay Cleaning', price: 25, duration: '30min', desc: 'Degrease and clean the engine bay.' }
		]
	},
	'Auto Repair & Vehicle Maintenance': {
		icon: 'dashicons-shield-alt',
		category: 'Auto Repair',
		items: [
			{ name: 'Oil Change', price: 40, duration: '30min', desc: 'Engine oil and filter replacement.' },
			{ name: 'Brake Inspection & Pad Replacement', price: 90, duration: '1h', desc: 'Inspect and replace worn brake pads.' },
			{ name: 'Tire Rotation & Balancing', price: 35, duration: '45min', desc: 'Rotate and balance all four tires.' },
			{ name: 'Battery Check & Replacement', price: 50, duration: '30min', desc: 'Test battery health and replace if needed.' },
			{ name: 'General Diagnostic Check-up', price: 60, duration: '1h', desc: 'Full vehicle diagnostic scan and report.' }
		]
	},
	'Car / Vehicle Rental': {
		icon: 'dashicons-cart',
		items: [
			{ name: 'Economy Car (Daily)', price: 35, duration: '1 day', desc: 'Compact car rental, unlimited mileage.' },
			{ name: 'SUV Rental (Daily)', price: 65, duration: '1 day', desc: 'Spacious SUV for family or group travel.' },
			{ name: 'Luxury Car Rental (Daily)', price: 150, duration: '1 day', desc: 'Premium vehicle for special occasions.' },
			{ name: 'Weekly Rental Package', price: 200, duration: '7 days', desc: 'Discounted weekly rate for any vehicle class.' },
			{ name: 'Airport Pickup/Drop-off', price: 20, duration: '30min', desc: 'Convenient transfer service to/from the airport.' }
		]
	},
	'Hair Salon & Barbershop': {
		icon: 'dashicons-art',
		category: 'Hair Services',
		items: [
			{ name: "Men's Haircut", price: 20, duration: '30min', desc: 'Classic haircut with clippers and scissors.' },
			{ name: "Women's Haircut & Style", price: 40, duration: '1h', desc: 'Precision cut with blow-dry styling.' },
			{ name: 'Hair Coloring', price: 70, duration: '1h30min', desc: 'Full color or root touch-up service.' },
			{ name: 'Beard Trim & Shape-up', price: 15, duration: '20min', desc: 'Neat beard trim and edge line-up.' },
			{ name: 'Blowout & Styling', price: 30, duration: '45min', desc: 'Wash, blow-dry, and finishing style.' }
		]
	},
	'Spa, Massage & Beauty Treatments': {
		icon: 'dashicons-heart',
		category: 'Spa & Massage',
		items: [
			{ name: 'Swedish Massage (60 min)', price: 60, duration: '1h', desc: 'Relaxing full-body massage.' },
			{ name: 'Deep Tissue Massage', price: 75, duration: '1h', desc: 'Targeted massage for muscle tension relief.' },
			{ name: 'Facial Treatment', price: 55, duration: '45min', desc: 'Cleansing, exfoliating, and hydrating facial.' },
			{ name: 'Hot Stone Therapy', price: 85, duration: '1h15min', desc: 'Heated stone massage for deep relaxation.' },
			{ name: 'Body Scrub & Wrap', price: 70, duration: '1h', desc: 'Exfoliating scrub with moisturizing wrap.' }
		]
	},
	'Nail Salon': {
		icon: 'dashicons-star-filled',
		items: [
			{ name: 'Classic Manicure', price: 20, duration: '30min', desc: 'Nail shaping, cuticle care, and polish.' },
			{ name: 'Classic Pedicure', price: 30, duration: '45min', desc: 'Foot soak, exfoliation, and polish.' },
			{ name: 'Gel Manicure', price: 35, duration: '45min', desc: 'Long-lasting gel polish application.' },
			{ name: 'Acrylic Full Set', price: 50, duration: '1h15min', desc: 'Full acrylic nail extensions.' },
			{ name: 'Nail Art Add-on', price: 10, duration: '15min', desc: 'Custom nail art design per set.' }
		]
	},
	'Medical & Dental Clinics': {
		icon: 'dashicons-shield',
		category: 'Medical & Dental',
		items: [
			{ name: 'General Consultation', price: 50, duration: '30min', desc: 'Initial doctor consultation and check-up.' },
			{ name: 'Dental Cleaning', price: 60, duration: '45min', desc: 'Routine teeth cleaning and polishing.' },
			{ name: 'Tooth Filling', price: 90, duration: '1h', desc: 'Cavity treatment and filling.' },
			{ name: 'Follow-up Visit', price: 30, duration: '20min', desc: 'Short follow-up consultation.' },
			{ name: 'Health Screening Package', price: 120, duration: '1h30min', desc: 'Comprehensive basic health screening.' }
		]
	},
	'Physiotherapy & Chiropractic': {
		icon: 'dashicons-universal-access',
		items: [
			{ name: 'Initial Assessment', price: 60, duration: '45min', desc: 'First-visit evaluation and treatment plan.' },
			{ name: 'Chiropractic Adjustment', price: 45, duration: '30min', desc: 'Spinal alignment and adjustment session.' },
			{ name: 'Physiotherapy Session', price: 55, duration: '45min', desc: 'Targeted rehab and mobility exercises.' },
			{ name: 'Sports Injury Treatment', price: 70, duration: '1h', desc: 'Specialized care for sports-related injuries.' },
			{ name: 'Follow-up Therapy Session', price: 40, duration: '30min', desc: 'Ongoing treatment follow-up.' }
		]
	},
	'Fitness Training & Gym Sessions': {
		icon: 'dashicons-awards',
		items: [
			{ name: 'Personal Training (1 hr)', price: 50, duration: '1h', desc: 'One-on-one personalized workout session.' },
			{ name: 'Group Fitness Class', price: 15, duration: '1h', desc: 'Small-group training class.' },
			{ name: 'Fitness Assessment', price: 30, duration: '45min', desc: 'Body composition and fitness level evaluation.' },
			{ name: 'Nutrition Consultation', price: 40, duration: '30min', desc: 'Personalized diet and nutrition planning.' },
			{ name: 'Small Group Bootcamp', price: 20, duration: '1h', desc: 'High-intensity group workout session.' }
		]
	},
	'Yoga & Wellness Classes': {
		icon: 'dashicons-smiley',
		items: [
			{ name: 'Drop-in Yoga Class', price: 15, duration: '1h', desc: 'Single yoga class session, all levels welcome.' },
			{ name: 'Private Yoga Session', price: 60, duration: '1h', desc: 'One-on-one personalized yoga instruction.' },
			{ name: 'Meditation & Mindfulness Session', price: 20, duration: '45min', desc: 'Guided meditation for stress relief.' },
			{ name: 'Prenatal Yoga Class', price: 25, duration: '1h', desc: 'Gentle yoga tailored for expecting mothers.' },
			{ name: 'Wellness Consultation', price: 35, duration: '30min', desc: 'Personalized wellness and lifestyle guidance.' }
		]
	},
	'Photography & Videography Sessions': {
		icon: 'dashicons-camera',
		items: [
			{ name: 'Portrait Photo Session', price: 100, duration: '1h', desc: 'Studio or outdoor portrait photoshoot.' },
			{ name: 'Event Photography (Half Day)', price: 350, duration: '4h', desc: 'Professional photo coverage for events.' },
			{ name: 'Wedding Videography Package', price: 800, duration: '8h', desc: 'Full-day wedding video coverage.' },
			{ name: 'Product Photography', price: 150, duration: '2h', desc: 'Professional product shots for e-commerce.' },
			{ name: 'Photo Editing & Retouching', price: 40, duration: '1h', desc: 'Post-production editing per photo set.' }
		]
	},
	'Home Cleaning Services': {
		icon: 'dashicons-admin-home',
		category: 'Home Cleaning',
		items: [
			{ name: 'Standard Home Cleaning', price: 60, duration: '2h', desc: 'General cleaning of living areas and kitchen.' },
			{ name: 'Deep Cleaning Service', price: 120, duration: '4h', desc: 'Thorough top-to-bottom deep clean.' },
			{ name: 'Move-in/Move-out Cleaning', price: 150, duration: '4h', desc: 'Complete cleaning for moving transitions.' },
			{ name: 'Carpet & Upholstery Cleaning', price: 80, duration: '1h30min', desc: 'Steam cleaning for carpets and furniture.' },
			{ name: 'Window Cleaning', price: 40, duration: '1h', desc: 'Interior and exterior window washing.' }
		]
	},
	'Pest Control Services': {
		icon: 'dashicons-visibility',
		items: [
			{ name: 'General Pest Inspection', price: 40, duration: '45min', desc: 'Full property inspection for pest activity.' },
			{ name: 'Termite Treatment', price: 150, duration: '2h', desc: 'Targeted termite extermination service.' },
			{ name: 'Rodent Control Package', price: 100, duration: '1h30min', desc: 'Trapping and prevention for rodents.' },
			{ name: 'Mosquito & Insect Spray', price: 60, duration: '1h', desc: 'Yard and perimeter insect spraying.' },
			{ name: 'Quarterly Pest Control Plan', price: 200, duration: '1h', desc: 'Recurring preventive pest treatment visit.' }
		]
	},
	'Appliance & Electronics Repair': {
		icon: 'dashicons-admin-tools',
		items: [
			{ name: 'Appliance Diagnostic Check', price: 30, duration: '30min', desc: 'Identify the issue with your appliance.' },
			{ name: 'Refrigerator Repair', price: 90, duration: '1h30min', desc: 'Diagnose and repair refrigerator issues.' },
			{ name: 'Washing Machine Repair', price: 80, duration: '1h', desc: 'Repair service for washers and dryers.' },
			{ name: 'TV / Electronics Repair', price: 70, duration: '1h', desc: 'Screen and electronics troubleshooting & repair.' },
			{ name: 'AC Unit Repair & Servicing', price: 100, duration: '1h30min', desc: 'Air conditioning repair and maintenance.' }
		]
	},
	'Plumbing, Electrical & Handyman Services': {
		icon: 'dashicons-networking',
		items: [
			{ name: 'Plumbing Inspection', price: 40, duration: '30min', desc: 'General plumbing system check-up.' },
			{ name: 'Leak Repair', price: 70, duration: '1h', desc: 'Fix leaking pipes or fixtures.' },
			{ name: 'Electrical Wiring Check', price: 60, duration: '1h', desc: 'Safety inspection of electrical wiring.' },
			{ name: 'Fixture Installation', price: 50, duration: '45min', desc: 'Install lighting, faucets, or fixtures.' },
			{ name: 'General Handyman Hour', price: 45, duration: '1h', desc: 'Hourly rate for general home repairs.' }
		]
	},
	'Tutoring & Private Lessons': {
		icon: 'dashicons-book',
		items: [
			{ name: 'One-on-One Tutoring (1 hr)', price: 30, duration: '1h', desc: 'Personalized academic tutoring session.' },
			{ name: 'Group Study Session', price: 15, duration: '1h', desc: 'Small group tutoring for shared topics.' },
			{ name: 'Exam Prep Session', price: 40, duration: '1h30min', desc: 'Focused test/exam preparation session.' },
			{ name: 'Homework Help Session', price: 25, duration: '45min', desc: 'Guided homework assistance.' },
			{ name: 'Language Learning Class', price: 30, duration: '1h', desc: 'Conversational language lesson.' }
		]
	},
	'Music Lessons & Instrument Classes': {
		icon: 'dashicons-microphone',
		items: [
			{ name: 'Guitar Lesson (1 hr)', price: 35, duration: '1h', desc: 'Beginner to advanced guitar instruction.' },
			{ name: 'Piano Lesson (1 hr)', price: 40, duration: '1h', desc: 'Personalized piano lessons for all levels.' },
			{ name: 'Vocal / Singing Lesson', price: 35, duration: '1h', desc: 'One-on-one vocal coaching.' },
			{ name: 'Drum Lesson', price: 35, duration: '1h', desc: 'Rhythm and drumming fundamentals.' },
			{ name: 'Group Music Class', price: 20, duration: '1h', desc: 'Small group instrument or music theory class.' }
		]
	},
	'Driving Lessons': {
		icon: 'dashicons-flag',
		items: [
			{ name: 'Beginner Driving Lesson (1 hr)', price: 45, duration: '1h', desc: 'Introductory driving lesson for new drivers.' },
			{ name: 'Highway Driving Practice', price: 50, duration: '1h', desc: 'Practice session focused on highway driving.' },
			{ name: 'Road Test Preparation', price: 60, duration: '1h30min', desc: 'Mock test and final exam preparation.' },
			{ name: 'Defensive Driving Course', price: 80, duration: '2h', desc: 'Safety-focused defensive driving training.' },
			{ name: '5-Lesson Starter Package', price: 200, duration: '1h', desc: 'Bundle discount for new drivers (5 sessions).' }
		]
	},
	'Pet Grooming & Pet Sitting': {
		icon: 'dashicons-heart',
		category: 'Pet Care',
		items: [
			{ name: 'Dog Bath & Brush', price: 25, duration: '45min', desc: 'Wash, dry, and brush-out for dogs.' },
			{ name: 'Full Grooming Package', price: 55, duration: '1h30min', desc: 'Bath, haircut, nail trim, and ear cleaning.' },
			{ name: 'Cat Grooming', price: 40, duration: '1h', desc: 'Bathing and brushing service for cats.' },
			{ name: 'Nail Trimming', price: 10, duration: '15min', desc: 'Quick nail trim for pets.' },
			{ name: 'Pet Sitting (Per Visit)', price: 20, duration: '30min', desc: 'Drop-in visit to feed and check on pets.' }
		]
	},
	'Event Planning & Equipment Rental': {
		icon: 'dashicons-calendar-alt',
		category: 'Event Services',
		items: [
			{ name: 'Event Consultation', price: 50, duration: '1h', desc: 'Initial planning consultation for your event.' },
			{ name: 'Chairs & Tables Rental (Package)', price: 100, duration: '1 day', desc: 'Rental package for seating and tables.' },
			{ name: 'Sound System Rental', price: 150, duration: '1 day', desc: 'PA system and speaker rental for events.' },
			{ name: 'Tent & Canopy Rental', price: 200, duration: '1 day', desc: 'Outdoor event tent setup and rental.' },
			{ name: 'Full Event Planning Package', price: 500, duration: '1 day', desc: 'End-to-end event coordination service.' }
		]
	},
	'Consulting (Legal, Financial, Business)': {
		icon: 'dashicons-businessman',
		items: [
			{ name: 'Initial Consultation (30 min)', price: 50, duration: '30min', desc: 'Introductory consultation to discuss your needs.' },
			{ name: 'Legal Consultation (1 hr)', price: 150, duration: '1h', desc: 'In-depth legal advice session.' },
			{ name: 'Financial Planning Session', price: 120, duration: '1h', desc: 'Personalized financial planning consultation.' },
			{ name: 'Business Strategy Session', price: 130, duration: '1h', desc: 'Strategic planning for your business.' },
			{ name: 'Follow-up Consultation', price: 60, duration: '30min', desc: 'Short follow-up advisory session.' }
		]
	},
	'Co-working Space / Room Booking': {
		icon: 'dashicons-desktop',
		items: [
			{ name: 'Hot Desk (Daily)', price: 15, duration: '1 day', desc: 'Single-day access to shared workspace.' },
			{ name: 'Private Office (Daily)', price: 50, duration: '1 day', desc: 'Private office rental for the day.' },
			{ name: 'Meeting Room (Hourly)', price: 25, duration: '1h', desc: 'Meeting room rental per hour.' },
			{ name: 'Conference Room (Half Day)', price: 100, duration: '4h', desc: 'Larger conference room for half-day events.' },
			{ name: 'Monthly Desk Membership', price: 150, duration: '1 day', desc: 'Access to a dedicated desk for the month.' }
		]
	},
	'Restaurant Table Reservations': {
		icon: 'dashicons-food',
		items: [
			{ name: 'Table for 2', price: 0, duration: '1h30min', desc: 'Reserve a table for two guests.' },
			{ name: 'Table for 4', price: 0, duration: '1h30min', desc: 'Reserve a table for four guests.' },
			{ name: 'Private Dining Room', price: 50, duration: '2h', desc: 'Reserved private room for special occasions.' },
			{ name: "Chef's Tasting Experience", price: 80, duration: '2h', desc: 'Multi-course tasting menu reservation.' },
			{ name: 'Outdoor Patio Seating', price: 0, duration: '1h30min', desc: 'Reserve a table on the outdoor patio.' }
		]
	},
	'Tour & Activity Booking': {
		icon: 'dashicons-palmtree',
		items: [
			{ name: 'City Walking Tour', price: 25, duration: '2h', desc: 'Guided walking tour of local highlights.' },
			{ name: 'Half-Day Sightseeing Tour', price: 60, duration: '4h', desc: 'Guided tour covering key attractions.' },
			{ name: 'Full-Day Excursion', price: 120, duration: '8h', desc: 'All-day guided tour with transport included.' },
			{ name: 'Adventure Activity (Hiking)', price: 45, duration: '3h', desc: 'Guided outdoor adventure activity.' },
			{ name: 'Private Guided Tour', price: 150, duration: '3h', desc: 'Personalized private tour experience.' }
		]
	},
	'Pool & Spa Maintenance Services': {
		icon: 'dashicons-admin-tools',
		items: [
			{ name: 'Pool Cleaning (Standard)', price: 60, duration: '1h', desc: 'Skimming, vacuuming, and chemical balancing.' },
			{ name: 'Spa/Hot Tub Cleaning', price: 50, duration: '45min', desc: 'Cleaning and water balancing for spas.' },
			{ name: 'Filter Replacement', price: 40, duration: '30min', desc: 'Pool/spa filter inspection and replacement.' },
			{ name: 'Chemical Balancing Only', price: 25, duration: '20min', desc: 'Water testing and chemical adjustment.' },
			{ name: 'Monthly Maintenance Plan', price: 180, duration: '1h', desc: 'Recurring monthly pool maintenance visit.' }
		]
	},
	'Recurring Subscription-Based Services': {
		icon: 'dashicons-controls-repeat',
		items: [
			{ name: 'Weekly Cleaning Subscription', price: 50, duration: '2h', desc: 'Recurring weekly home cleaning visit.' },
			{ name: 'Bi-Weekly Lawn Care', price: 40, duration: '1h', desc: 'Recurring lawn mowing and yard maintenance.' },
			{ name: 'Monthly Pest Control Plan', price: 60, duration: '1h', desc: 'Recurring monthly pest prevention visit.' },
			{ name: 'Monthly Pool Maintenance', price: 100, duration: '1h', desc: 'Recurring monthly pool service visit.' },
			{ name: 'Weekly Meal Prep Delivery', price: 80, duration: '30min', desc: 'Recurring weekly meal preparation & delivery.' }
		]
	}
};
