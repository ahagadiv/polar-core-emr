# POLAR Healthcare - Customized Appointment System Guide

## 🎯 **Overview**
The appointment system has been customized specifically for POLAR Healthcare's service lines:
- **VASCULAR ACCESS**
- **HOME HEALTH** 
- **HOME INFUSION**
- **HOME DIALYSIS**

---

## 📋 **Appointment Status Options**

### **🔄 Basic Status Flow:**
1. **S - Scheduled** → **C - Confirmed** → **T - Traveling** → **A - Arrived** → **IP - In Progress** → **V - Visit Complete** → **D - Documentation Complete**

### **🚨 POLAR STAT - Rapid Response:**
- **STAT - POLAR STAT - Urgent**: This status will **BLINK/FLASH** on the calendar to indicate urgency
- Use for emergency PICC/MIDLINE placements
- Automatically highlights in red with blinking animation

### **📱 Communication Status:**
- **SMS - Text Confirmed**: Patient confirmed via text message
- **CALL - Phone Confirmed**: Patient confirmed via phone call  
- **EMAIL - Email Confirmed**: Patient confirmed via email

### **⚠️ Issue Status:**
- **NS - No Show**: Patient didn't show up for appointment
- **CX - Cancelled**: Appointment was cancelled
- **W - Weather Delay**: Appointment delayed due to weather
- **I - Insurance Issue**: Appointment delayed due to insurance problems

### **🔍 Follow-up:**
- **F - Follow-up Required**: Patient needs follow-up visit
- **D - Documentation Complete**: All documentation finished

---

## 🏥 **Care Settings (Formerly "Room Numbers")**

### **🏠 Primary Settings:**
- **Home Visit**: Default for most POLAR services
- **Clinic**: Services provided at POLAR clinic location

### **🏥 Facility-Based Care:**
- **Skilled Nursing Facility (SNF)**: Services at nursing homes
- **LTACH**: Long-term acute care hospital
- **Hospital**: Inpatient hospital services

### **🏢 Office Settings:**
- **POLAR | Office Visit**: Patient visits POLAR office for services

---

## 🎯 **How to Use the New System**

### **📅 Scheduling a Routine Visit:**
1. **Patient**: Select patient
2. **Assigned Clinician**: Choose who will provide care
3. **Category**: Select service type (Vascular Access, Home Health, etc.)
4. **Care Setting**: Choose "Home Visit" (default) or appropriate setting
5. **Status**: Start with "S - Scheduled"
6. **Save**

### **🚨 Scheduling a POLAR STAT (Urgent):**
1. Follow routine steps above
2. **Status**: Select "STAT - POLAR STAT - Urgent"
3. **Save** → Appointment will **BLINK** on calendar

### **📱 Confirming Appointments:**
- Update status to **SMS**, **CALL**, or **EMAIL** when patient confirms
- This helps track communication methods

### **🏥 Facility/Office Visits:**
- For SNF, LTACH, Hospital, or Office visits, select appropriate **Care Setting**
- **POLAR | Office Visit** = Patient coming to POLAR office
- Update status as clinician travels and arrives

---

## 🎨 **Visual Indicators**

### **🚨 POLAR STAT Appointments:**
- **Red border** with **blinking animation**
- **Bold text** to stand out
- **Red background tint**
- **Category labels** displayed on calendar events
- **"POLAR STAT"** label visible on calendar

### **📊 Status Colors:**
- **Blue**: Scheduled/Confirmed
- **Green**: Complete/Successful  
- **Yellow**: In Progress/Traveling
- **Red**: Urgent/Cancelled
- **Gray**: Cancelled/No Show

### **🏠 Care Setting Badges:**
- **Green badge**: Home Visit
- **Blue badge**: Clinic
- **Yellow badge**: SNF
- **Orange badge**: LTACH
- **Red badge**: Hospital
- **Purple badge**: POLAR | Office Visit

### **📋 Calendar Event Display:**
- **Patient names** with **category labels** (e.g., "McCaskill, Faith - Vascular Access")
- **POLAR STAT** appointments show **"McCaskill, Faith - POLAR STAT"**
- **Category labels** help identify service type at a glance
- **Clinicians** sidebar (formerly "Providers") for clearer staff identification

---

## 💡 **Best Practices**

### **✅ Do:**
- Use **"Home Visit"** as default care setting
- Set **POLAR STAT** status for urgent cases
- Update status as appointment progresses
- Use communication confirmations (SMS/CALL/EMAIL)

### **❌ Avoid:**
- Leaving status as "None" for active appointments
- Using generic room numbers
- Not updating status during visit progression

---

## 🔧 **Technical Notes**

### **Database Changes:**
- Customized `list_options` table for `apptstat` and `patient_flow_board_rooms`
- Added POLAR-specific status codes with visual indicators
- Replaced generic room numbers with care settings

### **Visual Enhancements:**
- Added CSS animations for POLAR STAT urgency
- Color-coded status indicators
- Care setting badges for easy identification

---

## 📞 **Support**
For questions about the customized appointment system, contact your POLAR Healthcare IT team.
