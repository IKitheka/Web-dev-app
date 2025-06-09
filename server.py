from flask import Flask, render_template, redirect, url_for, request, session, flash, make_response
import os
from datetime import datetime, timedelta

app = Flask(__name__)
app.secret_key = 'your-secret-key-here'  # Change this to a secure secret key

# Configure template and static folders
app.template_folder = 'Application'
app.static_folder = 'Application/static'

# Cache control decorator
def add_cache_headers(response):
    """Add cache control headers to prevent caching of sensitive pages"""
    response.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'
    response.headers['Pragma'] = 'no-cache'
    response.headers['Expires'] = '0'
    return response

def add_static_cache_headers(response):
    """Add cache headers for static files (CSS, JS, images)"""
    response.headers['Cache-Control'] = 'public, max-age=31536000'  # 1 year
    response.headers['ETag'] = f'"{hash(datetime.now().isoformat())}"'
    return response

# Apply cache penalty to all routes
@app.after_request
def after_request(response):
    # Apply no-cache headers to HTML pages (penalty for caching sensitive content)
    if response.content_type and 'text/html' in response.content_type:
        add_cache_headers(response)
    # Allow caching for static assets
    elif request.endpoint == 'static':
        add_static_cache_headers(response)
    
    # Add security headers
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['X-Frame-Options'] = 'DENY'
    response.headers['X-XSS-Protection'] = '1; mode=block'
    
    return response

# Home/Landing page
@app.route('/')
def home():
    return redirect(url_for('student_dashboard'))

# Dashboard routes
@app.route('/dashboard/student')
def student_dashboard():
    return render_template('Dashboards/student_dashboard.html')

@app.route('/dashboard/admin')
def admin_dashboard():
    return render_template('Dashboards/admin_dashboard.html')

@app.route('/dashboard/employer')
def employer_dashboard():
    return render_template('Dashboards/employer_dashboard.html')

# Profile routes
@app.route('/profile/student')
def student_profile():
    return render_template('Profiles/student_profile.html')

@app.route('/profile/admin')
def admin_profile():
    return render_template('Profiles/admin_profile.html')

@app.route('/profile/employer')
def employer_profile():
    return render_template('Profiles/employer_profile.html')

# Authentication routes (if you have auth templates)
@app.route('/login')
def login():
    # Check if login template exists
    login_path = os.path.join(app.template_folder, 'Authentication', 'login.html')
    if os.path.exists(login_path):
        return render_template('Authentication/login.html')
    else:
        return redirect(url_for('student_dashboard'))

@app.route('/register')
def register():
    # Check if register template exists
    register_path = os.path.join(app.template_folder, 'Authentication', 'register.html')
    if os.path.exists(register_path):
        return render_template('Authentication/register.html')
    else:
        return redirect(url_for('student_dashboard'))

@app.route('/logout')
def logout():
    session.clear()
    flash('You have been logged out successfully!', 'info')
    return redirect(url_for('login'))

# API routes for form submissions (you can expand these)
@app.route('/api/profile/update', methods=['POST'])
def update_profile():
    # Handle profile updates
    user_type = request.form.get('user_type', 'student')
    
    # Process form data here
    # For now, just redirect back to the profile
    flash('Profile updated successfully!', 'success')
    
    if user_type == 'admin':
        return redirect(url_for('admin_profile'))
    elif user_type == 'employer':
        return redirect(url_for('employer_profile'))
    else:
        return redirect(url_for('student_profile'))

# Navigation helper routes
@app.route('/internships')
def browse_internships():
    # Placeholder for internship browsing
    return redirect(url_for('student_dashboard'))

@app.route('/applications')
def my_applications():
    # Placeholder for applications page
    return redirect(url_for('student_dashboard'))

@app.route('/post-job')
def post_job():
    # Placeholder for job posting
    return redirect(url_for('employer_dashboard'))

@app.route('/manage-listings')
def manage_listings():
    # Placeholder for managing job listings
    return redirect(url_for('employer_dashboard'))

@app.route('/applicants')
def view_applicants():
    # Placeholder for viewing applicants
    return redirect(url_for('employer_dashboard'))

@app.route('/employers')
def manage_employers():
    # Placeholder for admin employer management
    return redirect(url_for('admin_dashboard'))

@app.route('/job-seekers')
def manage_job_seekers():
    # Placeholder for admin job seeker management
    return redirect(url_for('admin_dashboard'))

@app.route('/reports')
def view_reports():
    # Placeholder for admin reports
    return redirect(url_for('admin_dashboard'))

@app.route('/settings')
def settings():
    # Placeholder for settings page
    return redirect(url_for('admin_dashboard'))

# Error handlers
@app.errorhandler(404)
def not_found_error(error):
    return redirect(url_for('student_dashboard'))

@app.errorhandler(500)
def internal_error(error):
    return redirect(url_for('student_dashboard'))

# Development helper - list all routes
@app.route('/routes')
def list_routes():
    routes = []
    for rule in app.url_map.iter_rules():
        routes.append({
            'endpoint': rule.endpoint,
            'methods': list(rule.methods),
            'url': str(rule)
        })
    
    html = "<h1>Available Routes</h1><ul>"
    for route in routes:
        html += f"<li><strong>{route['endpoint']}</strong>: {route['url']} - {route['methods']}</li>"
    html += "</ul>"
    
    return html

if __name__ == '__main__':
    # Check if templates exist
    template_dir = 'Application'
    if not os.path.exists(template_dir):
        print(f"Error: Template directory '{template_dir}' not found!")
        exit(1)
    
    print("🌟 Intern Connect Server Starting...")
    print("📁 Template folder:", app.template_folder)
    print("📁 Static folder:", app.static_folder)
    print("")
    print("🚀 Available endpoints:")
    print("   • http://localhost:5000/ (redirects to student dashboard)")
    print("   • http://localhost:5000/dashboard/student")
    print("   • http://localhost:5000/dashboard/admin") 
    print("   • http://localhost:5000/dashboard/employer")
    print("   • http://localhost:5000/profile/student")
    print("   • http://localhost:5000/profile/admin")
    print("   • http://localhost:5000/profile/employer")
    print("   • http://localhost:5000/routes (see all routes)")
    print("")
    print("✨ Aurora theme will be applied to all pages!")
    
    app.run(debug=True, host='0.0.0.0', port=5000)