{
	text: 'Client Information Box',
	href: '#client-information-box',
	object: 'procedure',
	id: '1',
	name: 'client-information-box',
	tags: ['0']
  },{
	text: 'Member Loan Information',
	href: '#member_loan_information',
	object: 'group',
	id: '6',
	name: 'member_loan_information',
	tags: ['2'],
	nodes: [
	  {
		text: 'Loans Payable',
		href: '#loans_payable',
		object: 'field',
		field_type: 'amount',
		id: '16',
		name: 'loans_payable',
		tags: ['0']
	  },{
		text: 'Loan Availment',
		href: '#loan_availment',
		object: 'field',
		field_type: 'number',
		id: '15',
		name: 'loan_availment',
		tags: ['0']
	  },{
		text: 'Savings Deposit',
		href: '#total_savings',
		object: 'field',
		field_type: 'amount',
		id: '13',
		name: 'total_savings',
		tags: ['0']
	  },{
		text: 'Share Capital',
		href: '#share_capital',
		object: 'field',
		field_type: 'amount',
		id: '14',
		name: 'share_capital',
		tags: ['0']
	  },
	]
	},{
	text: 'Loan Details',
	href: '#loan_details',
	object: 'group',
	id: '5',
	name: 'loan_details',
	tags: ['2'],
	nodes: [
	  {
		text: 'Loan ID',
		href: '#loan_id',
		object: 'field',
		field_type: 'hidden',
		id: '28',
		name: 'loan_id',
		tags: ['0']
	  },{
		text: 'Loan Type',
		href: '#loan_types',
		object: 'field',
		field_type: 'select',
		id: '7',
		name: 'loan_types',
		tags: ['0']
	  },{
		text: 'Payment Mode',
		href: '#payment_mode',
		object: 'field',
		field_type: 'select',
		id: '11',
		name: 'payment_mode',
		tags: ['0']
	  },{
		text: 'Loan Amount',
		href: '#loan_amount',
		object: 'field',
		field_type: 'amount',
		id: '8',
		name: 'loan_amount',
		tags: ['0']
	  },{
		text: 'Loan Terms',
		href: '#payment_terms',
		object: 'field',
		field_type: 'number',
		id: '27',
		name: 'payment_terms',
		tags: ['0']
	  },{
		text: 'Loan Interest',
		href: '#loan_interest_percentage',
		object: 'field',
		field_type: 'number',
		id: '23',
		name: 'loan_interest_percentage',
		tags: ['0']
	  },{
		text: 'Loan Purpose',
		href: '#loan_purpose',
		object: 'field',
		field_type: 'textarea',
		id: '12',
		name: 'loan_purpose',
		tags: ['0']
	  },
	]
	},{
	text: 'Members Credit Rating',
	href: '#members_credit_rating',
	object: 'group',
	id: '9',
	name: 'members_credit_rating',
	tags: ['2'],
	nodes: [
	  {
		text: 'Willingness To Pay',
		href: '#willingness_pay',
		object: 'field',
		field_type: 'select',
		id: '17',
		name: 'willingness_pay',
		tags: ['0']
	  },{
		text: 'Ability To Pay',
		href: '#ability_pay',
		object: 'field',
		field_type: 'amount',
		id: '18',
		name: 'ability_pay',
		tags: ['0']
	  },{
		text: 'Collateral',
		href: '#collateral_pay',
		object: 'field',
		field_type: 'amount',
		id: '19',
		name: 'collateral_pay',
		tags: ['0']
	  },{
		text: 'Rating Notes',
		href: '#remarks_text',
		object: 'field',
		field_type: 'text',
		id: '20',
		name: 'remarks_text',
		tags: ['0']
	  },
	]
	},{
	text: 'Loan Summary',
	href: '#loan_summary',
	object: 'group',
	id: '10',
	name: 'loan_summary',
	tags: ['2'],
	nodes: [
	  {
		text: 'Loan Granted',
		href: '#loan_granted',
		object: 'field',
		field_type: 'amount',
		id: '10',
		name: 'loan_granted',
		tags: ['0']
	  },{
		text: 'Period Interest',
		href: '#loan_interest',
		object: 'field',
		field_type: 'amount',
		id: '9',
		name: 'loan_interest',
		tags: ['0']
	  },{
		text: 'Loan Remarks',
		href: '#other_information',
		object: 'field',
		field_type: 'textarea',
		id: '22',
		name: 'other_information',
		tags: ['0']
	  },{
		text: 'Other Charges',
		href: '#other_charges',
		object: 'field',
		field_type: 'hidden',
		id: '26',
		name: 'other_charges',
		tags: ['0']
	  },{
		text: 'Documents/Attachments',
		href: '#attachments',
		object: 'field',
		field_type: 'hidden',
		id: '29',
		name: 'attachments',
		tags: ['0']
	  },{
		text: 'Loan Summary Procedure',
		href: '#loan_summary_procedure',
		object: 'procedure',
		id: '3',
		name: 'loan_summary_procedure',
		tags: ['0']
	  },
	]
	},{
	text: 'Loan Application Procedure',
	href: '#loan_application_procedure',
	object: 'procedure',
	id: '2',
	name: 'loan_application_procedure',
	tags: ['0']
	  }