-- POLAR Healthcare Custom Appointment Status and Care Settings
-- This script customizes appointment statuses and room numbers for POLAR's service lines

-- =====================================================
-- 1. CUSTOMIZE APPOINTMENT STATUSES FOR POLAR HEALTHCARE
-- =====================================================

-- Clear existing appointment statuses (except None)
DELETE FROM list_options WHERE list_id = 'apptstat' AND option_id != '-';

-- Insert POLAR-specific appointment statuses
INSERT INTO list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options) VALUES
('apptstat', 'S', 'S Scheduled', 10, 0, 0, '', 'E6F3FF|0', '', 0, 0, 1, '', 1),
('apptstat', 'C', 'C Confirmed', 15, 0, 0, '', 'E6FFE6|0', '', 0, 0, 1, '', 1),

-- POLAR STAT - Rapid Response (with special indicators)
('apptstat', 'STAT', 'STAT POLAR STAT - Urgent', 20, 0, 0, '', 'FF4444|1', '', 1, 0, 1, '', 1),

-- Travel and arrival
('apptstat', 'T', 'T Traveling', 25, 0, 0, '', 'FFF2CC|0', '', 0, 0, 1, '', 1),
('apptstat', 'A', 'A Arrived', 30, 0, 0, '', 'FFE6CC|0', '', 0, 0, 1, '', 1),
('apptstat', 'L', 'L Late Arrival', 35, 0, 0, '', 'FFCCCC|0', '', 0, 0, 1, '', 1),

-- In progress
('apptstat', 'IP', 'IP In Progress', 40, 0, 0, '', 'CCE6FF|0', '', 0, 0, 1, '', 1),
('apptstat', 'V', 'V Visit Complete', 45, 0, 0, '', 'CCFFCC|0', '', 0, 0, 1, '', 1),

-- Completion and follow-up
('apptstat', 'F', 'F Follow-up Required', 50, 0, 0, '', 'FFFFCC|0', '', 0, 0, 1, '', 1),
('apptstat', 'D', 'D Documentation Complete', 55, 0, 0, '', 'E6FFE6|0', '', 0, 0, 1, '', 1),

-- Issues and cancellations
('apptstat', 'NS', 'NS No Show', 60, 0, 0, '', 'FFCCCC|0', '', 0, 0, 1, '', 1),
('apptstat', 'CX', 'CX Cancelled', 65, 0, 0, '', 'CCCCCC|0', '', 0, 0, 1, '', 1),
('apptstat', 'W', 'W Weather Delay', 70, 0, 0, '', 'DDDDDD|0', '', 0, 0, 1, '', 1),
('apptstat', 'I', 'I Insurance Issue', 75, 0, 0, '', 'FFE6CC|0', '', 0, 0, 1, '', 1),

-- Communication confirmations
('apptstat', 'SMS', 'SMS Text Confirmed', 80, 0, 0, '', 'E6F3FF|0', '', 0, 0, 1, '', 1),
('apptstat', 'CALL', 'CALL Phone Confirmed', 85, 0, 0, '', 'F0FFE8|0', '', 0, 0, 1, '', 1),
('apptstat', 'EMAIL', 'EMAIL Email Confirmed', 90, 0, 0, '', 'FFEBE3|0', '', 0, 0, 1, '', 1);

-- =====================================================
-- 2. CUSTOMIZE ROOM NUMBERS → CARE SETTINGS
-- =====================================================

-- Clear existing room numbers
DELETE FROM list_options WHERE list_id = 'patient_flow_board_rooms';

-- Insert POLAR-specific care settings
INSERT INTO list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options) VALUES
-- Primary care settings
('patient_flow_board_rooms', 'HOME', 'Home Visit', 10, 1, 0, '', 'E6FFE6|0', '', 0, 0, 1, '', 1),
('patient_flow_board_rooms', 'CLINIC', 'POLAR Clinic', 20, 0, 0, '', 'E6F3FF|0', '', 0, 0, 1, '', 1),

-- Facility-based care
('patient_flow_board_rooms', 'SNF', 'Skilled Nursing Facility', 30, 0, 0, '', 'FFF2CC|0', '', 0, 0, 1, '', 1),
('patient_flow_board_rooms', 'LTACH', 'LTACH', 40, 0, 0, '', 'FFE6CC|0', '', 0, 0, 1, '', 1),
('patient_flow_board_rooms', 'HOSPITAL', 'Hospital', 50, 0, 0, '', 'FFCCCC|0', '', 0, 0, 1, '', 1),

-- Special settings
('patient_flow_board_rooms', 'TELEHEALTH', 'Telehealth Visit', 60, 0, 0, '', 'CCE6FF|0', '', 0, 0, 1, '', 1),
('patient_flow_board_rooms', 'OFFICE', 'Office Visit', 70, 0, 0, '', 'CCFFCC|0', '', 0, 0, 1, '', 1);

-- =====================================================
-- 3. ADD SPECIAL INDICATORS FOR POLAR STAT
-- =====================================================

-- Add a note about POLAR STAT urgency
INSERT INTO list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options) VALUES
('polar_notes', 'STAT_INFO', 'POLAR STAT appointments will blink/flash on calendar to indicate urgency', 1, 0, 0, '', '', '', 0, 0, 1, '', 1);
