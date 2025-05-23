<?php


?>

<div class="mpwpv_service_list_container">

    <div class="mpwpv_service_list_header">
        <h1>Service Management Dashboard</h1>
        <p>Manage your services and track performance</p>
    </div>

    <!-- Analytics Section -->
    <div class="mpwpv_service_list_analytics-container">
        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon blue">📊</div>
            <div class="mpwpv_service_list_analytics-text">
                <p>Total Services</p>
                <p>142</p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon green">👥</div>
            <div class="mpwpv_service_list_analytics-text">
                <p>Active Clients</p>
                <p>87</p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon purple">💲</div>
            <div class="mpwpv_service_list_analytics-text">
                <p>Monthly Revenue</p>
                <p>$28,650</p>
            </div>
        </div>

        <div class="mpwpv_service_list_analytics-card">
            <div class="mpwpv_service_list_analytics-icon orange">📅</div>
            <div class="mpwpv_service_list_analytics-text">
                <p>Upcoming Bookings</p>
                <p>23</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-card">
        <div class="card-header">
            <div class="header-top">
                <h2>Service Listings</h2>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search services...">
                    <span class="search-icon">🔍</span>
                </div>
            </div>

            <div class="filter-buttons">
                <button class="filter-btn active">All Items (15)</button>
                <button class="filter-btn">Published (13)</button>
                <button class="filter-btn">Draft (2)</button>
                <button class="filter-btn">Trash (0)</button>
            </div>
        </div>

        <!-- Services Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                <tr>
                    <th style="width: 25%">Service</th>
                    <th style="width: 20%">Location</th>
                    <th style="width: 40%">Popular Services</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr class="scheduled">
                    <td>
                        <div class="status-indicator status-available">
                            <span class="status-dot"></span>
                            Available
                        </div>
                        <div class="service-name">Cleaning Service</div>
                    </td>
                    <td class="location">Downtown Office Complex</td>
                    <td>
                        <div class="service-tags">
                                    <span class="service-tag blue">
                                        <span class="tag-dot"></span>
                                        Office Cleaning
                                    </span>
                            <span class="service-tag green">
                                        <span class="tag-dot"></span>
                                        Carpet Cleaning
                                    </span>
                            <button class="expand-btn">+2 more</button>
                        </div>
                    </td>
                    <td>
                        <div class="rating">
                            <span class="rating-value">4.8</span>
                            <div class="stars">
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view">👁</button>
                            <button class="action-btn edit">✏️</button>
                            <button class="action-btn delete">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="service-name">Cleaning Service</div>
                    </td>
                    <td class="location">Green Valley Residential Area</td>
                    <td>
                        <div class="service-tags">
                                    <span class="service-tag blue">
                                        <span class="tag-dot"></span>
                                        Residential Cleaning
                                    </span>
                            <span class="service-tag green">
                                        <span class="tag-dot"></span>
                                        Move-in/Move-out
                                    </span>
                            <button class="expand-btn">+2 more</button>
                        </div>
                    </td>
                    <td>
                        <div class="rating">
                            <span class="rating-value">4.5</span>
                            <div class="stars">
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star empty"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view">👁</button>
                            <button class="action-btn edit">✏️</button>
                            <button class="action-btn delete">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="service-name">Cleaning Service</div>
                    </td>
                    <td class="location">Sunset Restaurant &amp; Bar</td>
                    <td>
                        <div class="service-tags">
                                    <span class="service-tag yellow">
                                        <span class="tag-dot"></span>
                                        Kitchen Cleaning
                                    </span>
                            <span class="service-tag purple">
                                        <span class="tag-dot"></span>
                                        Dining Area
                                    </span>
                            <button class="expand-btn">+2 more</button>
                        </div>
                    </td>
                    <td>
                        <div class="rating">
                            <span class="rating-value">5.0</span>
                            <div class="stars">
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view">👁</button>
                            <button class="action-btn edit">✏️</button>
                            <button class="action-btn delete">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr class="scheduled">
                    <td>
                        <div class="status-indicator status-available">
                            <span class="status-dot"></span>
                            Available
                        </div>
                        <div class="service-name">Car Wash</div>
                    </td>
                    <td class="location">Oceanview Auto Center</td>
                    <td>
                        <div class="service-tags">
                                    <span class="service-tag purple">
                                        <span class="tag-dot"></span>
                                        Premium Package
                                    </span>
                        </div>
                    </td>
                    <td>
                        <div class="rating">
                            <span class="rating-value">4.2</span>
                            <div class="stars">
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star empty"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view">👁</button>
                            <button class="action-btn edit">✏️</button>
                            <button class="action-btn delete">🗑</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="status-indicator status-draft">
                            <span class="status-dot"></span>
                            Draft
                        </div>
                        <div class="service-name">Gym Training Appointment</div>
                    </td>
                    <td class="location">Metro Fitness Center</td>
                    <td>
                        <div class="service-tags">
                                    <span class="service-tag purple">
                                        <span class="tag-dot"></span>
                                        Personal Training
                                    </span>
                        </div>
                    </td>
                    <td>
                        <div class="rating">
                            <span class="rating-value">4.7</span>
                            <div class="stars">
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                                <span class="star filled"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view">👁</button>
                            <button class="action-btn edit">✏️</button>
                            <button class="action-btn delete">🗑</button>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">15</span> results
            </div>
            <div class="pagination-nav">
                <button class="pagination-btn disabled">‹</button>
                <button class="pagination-btn active">1</button>
                <button class="pagination-btn">2</button>
                <button class="pagination-btn">3</button>
                <button class="pagination-btn">›</button>
            </div>
        </div>
    </div>
</div>
