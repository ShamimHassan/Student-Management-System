$(document).ready(function() {
    
    // Student Management Functions
    // =============================
    
    // Edit Student Modal
    $('#studentsTable').on('click', '.edit-btn', function() {
        var student = $(this).data('student');
        $('#edit_id').val(student.id);
        $('#edit_first_name').val(student.first_name);
        $('#edit_last_name').val(student.last_name);
        $('#edit_email').val(student.email);
        $('#edit_phone').val(student.phone);
        $('#edit_address').val(student.address);
        $('#edit_dob').val(student.date_of_birth);
        $('#edit_status').val(student.status);
        $('#editStudentModal').modal('show');
    });

    // Delete Student Confirmation
    $('#studentsTable').on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#delete_id').val(id);
        $('#delete_name').text(name);
        $('#deleteModal').modal('show');
    });

    // Add Student via AJAX
    $('#addStudentModal form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        var data = $form.serialize() + '&ajax=1';
        $.ajax({
            url: 'students.php',
            method: 'POST',
            data: data,
            success: function(response) {
                var res;
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (err) {
                    res = { success: false, message: 'Unexpected response' };
                }
                if (res.success && res.student) {
                    $('#addStudentModal').modal('hide');
                    $form[0].reset();
                    var s = res.student;
                    var rowHtml = '<tr>'
                        + '<td>' + $('<div>').text(s.student_id).html() + '</td>'
                        + '<td>' + $('<div>').text(s.first_name + ' ' + s.last_name).html() + '</td>'
                        + '<td>' + $('<div>').text(s.email).html() + '</td>'
                        + '<td>' + $('<div>').text(s.phone).html() + '</td>'
                        + '<td>' + (s.date_of_birth ? $('<div>').text(s.date_of_birth).html() : 'N/A') + '</td>'
                        + '<td><span class="badge bg-' + (s.status === 'active' ? 'success' : 'secondary') + '">' + (s.status.charAt(0).toUpperCase() + s.status.slice(1)) + '</span></td>'
                        + '<td>' + (s.created_at ? $('<div>').text(s.created_at).html() : '') + '</td>'
                        + '<td><div class="btn-group" role="group">'
                        + '<button class="btn btn-sm btn-outline-primary edit-btn" data-student=\'' + JSON.stringify(s) + '\'><i class="fas fa-edit"></i></button>'
                        + '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' + s.id + '" data-name="' + $('<div>').text(s.first_name + ' ' + s.last_name).html() + '"><i class="fas fa-trash"></i></button>'
                        + '</div></td>'
                        + '</tr>';
                    $('#studentsTable tbody').prepend(rowHtml);
                    showToast(res.message || 'Student added', 'success');
                } else {
                    showToast(res.message || 'Error adding student', 'danger');
                }
            },
            error: function() {
                showToast('Error adding student', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Edit Student via AJAX
    $('#editStudentModal form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        var data = $form.serialize() + '&ajax=1';
        $.ajax({
            url: 'students.php',
            method: 'POST',
            data: data,
            success: function(response) {
                var res;
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (err) {
                    res = { success: false, message: 'Unexpected response' };
                }
                if (res.success && res.student) {
                    $('#editStudentModal').modal('hide');
                    var s = res.student;
                    // Find and update the row
                    var $row = $('#studentsTable tbody tr').filter(function() {
                        return $(this).find('td:first').text() === s.student_id;
                    });
                    if ($row.length) {
                        $row.find('td').eq(1).text(s.first_name + ' ' + s.last_name);
                        $row.find('td').eq(2).text(s.email);
                        $row.find('td').eq(3).text(s.phone);
                        $row.find('td').eq(4).text(s.date_of_birth || 'N/A');
                        $row.find('td').eq(5).html('<span class="badge bg-' + (s.status === 'active' ? 'success' : 'secondary') + '">' + (s.status.charAt(0).toUpperCase() + s.status.slice(1)) + '</span>');
                        // Optionally update created_at if needed
                        $row.find('td').eq(6).text($row.find('td').eq(6).text());
                        // Update data-student attribute
                        $row.find('.edit-btn').data('student', s);
                        $row.find('.delete-btn').data('name', s.first_name + ' ' + s.last_name);
                    }
                    showToast(res.message || 'Student updated', 'success');
                } else {
                    showToast(res.message || 'Error updating student', 'danger');
                }
            },
            error: function() {
                showToast('Error updating student', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Delete Student via AJAX
    $('#deleteModal form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
        var data = $form.serialize() + '&ajax=1';
        $.ajax({
            url: 'students.php',
            method: 'POST',
            data: data,
            success: function(response) {
                var res;
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (err) {
                    res = { success: false, message: 'Unexpected response' };
                }
                if (res.success) {
                    $('#deleteModal').modal('hide');
                    // Remove the row
                    var id = $form.find('input[name="id"]').val();
                    $('#studentsTable tbody tr').filter(function() {
                        return $(this).find('.delete-btn').data('id') == id;
                    }).remove();
                    showToast(res.message || 'Student deleted', 'success');
                } else {
                    showToast(res.message || 'Error deleting student', 'danger');
                }
            },
            error: function() {
                showToast('Error deleting student', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Course Management Functions
    // ===========================
    
    // Edit Course Modal
    $('#coursesTable').on('click', '.edit-btn', function() {
        var course = $(this).data('course');
        $('#edit_id').val(course.id);
        $('#edit_course_code').val(course.course_code);
        $('#edit_course_name').val(course.course_name);
        $('#edit_description').val(course.description);
        $('#edit_credits').val(course.credits);
        $('#edit_fee').val(course.fee);
        $('#editCourseModal').modal('show');
    });
    
    // Delete Course Confirmation
    $('#coursesTable').on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#delete_id').val(id);
        $('#delete_name').text(name);
        $('#deleteModal').modal('show');
    });
    
    // Add Course via AJAX
    $('#addCourseModal form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        var data = $form.serialize() + '&ajax=1';
        $.ajax({
            url: 'courses.php',
            method: 'POST',
            data: data,
            success: function(response) {
                var res;
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (err) {
                    res = { success: false, message: 'Unexpected response' };
                }
                if (res.success && res.course) {
                    $('#addCourseModal').modal('hide');
                    $form[0].reset();
                    var c = res.course;
                    var desc = c.description || '';
                    var shortDesc = desc.length > 50 ? desc.substring(0, 50) + '...' : desc;
                    var rowHtml = '<tr>'
                        + '<td><strong>' + $('<div>').text(c.course_code).html() + '</strong></td>'
                        + '<td>' + $('<div>').text(c.course_name).html() + '</td>'
                        + '<td>' + $('<div>').text(shortDesc).html() + '</td>'
                        + '<td>' + c.credits + '</td>'
                        + '<td>' + parseFloat(c.fee).toFixed(2) + '</td>'
                        + '<td><span class="badge bg-info">0 students</span></td>'
                        + '<td>' + c.created_at_formatted + '</td>'
                        + '<td><div class="btn-group" role="group">'
                        + '<button type="button" class="btn btn-sm btn-outline-primary edit-btn" data-course=\'' + JSON.stringify(c) + '\'><i class="fas fa-edit"></i> Edit</button>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="' + c.id + '" data-name="' + $('<div>').text(c.course_name).html() + '"><i class="fas fa-trash"></i> Delete</button>'
                        + '</div></td>'
                        + '</tr>';
                    $('#coursesTable tbody').prepend(rowHtml);
                    showToast(res.message || 'Course added', 'success');
                } else {
                    showToast(res.message || 'Error adding course', 'danger');
                }
            },
            error: function() {
                showToast('Error adding course', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Seed Demo Data
    $('#seedDataBtn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Seeding...');
        $.ajax({
            url: 'seed_sample_data.php',
            method: 'POST',
            success: function(response) {
                var res;
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (err) {
                    res = { success: false, message: 'Unexpected response' };
                }
                if (res.success) {
                    showToast('Seed completed: ' + (res.students_created || 0) + ' students, ' + (res.courses || 0) + ' courses', 'success');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showToast(res.message || 'Seed failed', 'danger');
                }
            },
            error: function() {
                showToast('Seed failed', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    $('#clearSeedBtn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Clearing...');
        $.ajax({
            url: 'seed_delete_data.php',
            method: 'POST',
            success: function(response) {
                var res;
                try {
                    res = typeof response === 'object' ? response : JSON.parse(response);
                } catch (err) {
                    res = { success: false, message: 'Unexpected response' };
                }
                if (res.success) {
                    showToast(res.message || 'Demo data cleared', 'success');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showToast(res.message || 'Clear failed', 'danger');
                }
            },
            error: function() {
                showToast('Clear failed', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Result Management Functions
    // ===========================
    
    // Calculate percentage in real-time
    $('#marks_obtained, #total_marks').on('input', function() {
        var obtained = parseFloat($('#marks_obtained').val()) || 0;
        var total = parseFloat($('#total_marks').val()) || 1;
        var percentage = (obtained / total) * 100;
        $('#percentage').val(percentage.toFixed(2) + '%');
        
        // Calculate grade based on 5.0 scale
        var grade = '';
        if (percentage >= 80) grade = '5.00 (A+)';
        else if (percentage >= 70) grade = '4.00 (A)';
        else if (percentage >= 60) grade = '3.50 (A-)';
        else if (percentage >= 50) grade = '3.00 (B)';
        else if (percentage >= 40) grade = '2.00 (C)';
        else if (percentage >= 33) grade = '1.00 (D)';
        else grade = '0.00 (F)';
        
        $('#grade').val(grade);
    });
    
    // Edit Result Modal
    $('#resultsTable').on('click', '.edit-btn', function() {
        var result = $(this).data('result');
        var percentage = (result.marks_obtained / result.total_marks) * 100;
        
        $('#edit_id').val(result.id);
        $('#edit_exam_name').val(result.exam_name);
        $('#edit_marks_obtained').val(result.marks_obtained);
        $('#edit_total_marks').val(result.total_marks);
        $('#edit_exam_date').val(result.exam_date);
        $('#edit_percentage').val(percentage.toFixed(2) + '%');
        $('#editResultModal').modal('show');
    });
    
    // Update percentage when editing
    $('#edit_marks_obtained, #edit_total_marks').on('input', function() {
        var obtained = parseFloat($('#edit_marks_obtained').val()) || 0;
        var total = parseFloat($('#edit_total_marks').val()) || 1;
        var percentage = (obtained / total) * 100;
        $('#edit_percentage').val(percentage.toFixed(2) + '%');
    });
    
    // Delete Result Confirmation
    $('#resultsTable').on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var exam = $(this).data('exam');
        var student = $(this).data('student');
        $('#delete_id').val(id);
        $('#delete_exam').text(exam);
        $('#delete_student').text(student);
        $('#deleteModal').modal('show');
    });
    
    // Attendance Management Functions
    // ===============================
    
    // Load students when course is selected for attendance
    $('#attendance_course').on('change', function() {
        var courseId = $(this).val();
        if (courseId) {
            $.ajax({
                url: 'get_students_by_course.php',
                method: 'POST',
                data: { course_id: courseId },
                success: function(response) {
                    $('#attendanceTableBody').html(response);
                    $('#submitAttendance').prop('disabled', false);
                },
                error: function() {
                    $('#attendanceTableBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading students</td></tr>');
                    $('#submitAttendance').prop('disabled', true);
                }
            });
        } else {
            $('#attendanceTableBody').html('<tr><td colspan="4" class="text-center">Please select a course first</td></tr>');
            $('#submitAttendance').prop('disabled', true);
        }
    });
    
    // Delete Attendance Confirmation
    $('#attendanceTable').on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var student = $(this).data('student');
        var date = $(this).data('date');
        $('#delete_id').val(id);
        $('#delete_student').text(student);
        $('#delete_date').text(date);
        $('#deleteModal').modal('show');
    });
    
    // Payment Management Functions
    // ============================
    
    // Show course fee when course is selected
    $('#course_select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var fee = selectedOption.data('fee') || 0;
        $('#course_fee').val('৳' + parseFloat(fee).toFixed(2));
        $('#amount').attr('max', fee);
    });
    
    // Edit Payment Modal
    $('#paymentsTable').on('click', '.edit-btn', function() {
        var payment = $(this).data('payment');
        $('#edit_id').val(payment.id);
        $('#edit_amount').val(payment.amount);
        $('#edit_payment_date').val(payment.payment_date);
        $('#edit_status').val(payment.status);
        $('#edit_payment_method').val(payment.payment_method);
        $('#editPaymentModal').modal('show');
    });
    
    // Delete Payment Confirmation
    $('#paymentsTable').on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        $('#delete_id').val(id);
        $('#deleteModal').modal('show');
    });
    
    // Generate Payment Report
    $('#generateReport').on('click', function() {
        var fromDate = $('#report_from_date').val();
        var toDate = $('#report_to_date').val();
        
        if (!fromDate || !toDate) {
            alert('Please select both from and to dates');
            return;
        }
        
        $.ajax({
            url: 'generate_payment_report.php',
            method: 'POST',
            data: { 
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                $('#reportTableBody').html(response);
            },
            error: function() {
                $('#reportTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error generating report</td></tr>');
            }
        });
    });
    
    // General Functions
    // =================
    
    // Search functionality with delay
    var searchTimeout;
    $('input[name="search"]').on('input', function() {
        var $this = $(this);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $this.closest('form').submit();
        }, 500);
    });
    
    // Table sorting
    $('th').on('click', function() {
        var table = $(this).closest('table');
        var rows = table.find('tbody tr').toArray().sort(comparer($(this).index()));
        this.asc = !this.asc;
        if (!this.asc) {
            rows = rows.reverse();
        }
        table.find('tbody').empty().html(rows);
    });
    
    function comparer(index) {
        return function(a, b) {
            var valA = getCellValue(a, index);
            var valB = getCellValue(b, index);
            return $.isNumeric(valA) && $.isNumeric(valB) ? 
                valA - valB : valA.toString().localeCompare(valB);
        };
    }
    
    function getCellValue(row, index) {
        return $(row).children('td').eq(index).text();
    }
    
    // Form validation
    $('form').on('submit', function() {
        var isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        return isValid;
    });
    
    // Clear validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
    // Loading state for buttons
    $('button[type="submit"]').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        
        // Reset button after 3 seconds if form doesn't submit
        setTimeout(function() {
            $btn.prop('disabled', false).html(originalText);
        }, 3000);
    });
    
    // Toast notifications
    function showToast(message, type = 'success') {
        var toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0 fade show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        $('#toastContainer').append(toastHtml);
        
        setTimeout(function() {
            $('.toast').last().remove();
        }, 5000);
    }
    
    // Add toast container
    if ($('#toastContainer').length === 0) {
        $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }
    
    // Auto-hide alerts
    $('.alert').each(function() {
        var $alert = $(this);
        setTimeout(function() {
            $alert.fadeOut();
        }, 5000);
    });
    
    // Responsive table enhancement
    $('.table-responsive').each(function() {
        var $table = $(this).find('table');
        if ($table.width() > $(this).width()) {
            $(this).addClass('table-scrollable');
        }
    });
    
    // Print functionality
    $('.print-btn').on('click', function() {
        window.print();
    });
    
    // Export to CSV
    $('.export-csv').on('click', function() {
        var tableId = $(this).data('table');
        exportTableToCSV($('#' + tableId), 'export.csv');
    });
    
    function exportTableToCSV($table, filename) {
        var csv = [];
        var rows = $table.find('tr:visible');
        
        for (var i = 0; i < rows.length; i++) {
            var row = [];
            var cols = $(rows[i]).find('td, th');
            
            for (var j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }
            
            csv.push(row.join(','));
        }
        
        downloadCSV(csv.join('\n'), filename);
    }
    
    function downloadCSV(csv, filename) {
        var csvFile = new Blob([csv], { type: 'text/csv' });
        var downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
});
