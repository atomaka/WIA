class Link < ActiveRecord::Base
  attr_accessible :description, :release, :url
  validates :url,   :presence => true, :url => true
  validates :description, :presence => true

  def self.released
    Link.where("DATE(release) <= DATE(?)", Time.now).order("release DESC")
  end

  def self.get_and_count(id = nil)
    return false if nil

    link = nil
    Link.transaction do
      link = Link.find(id)
      link.visits += 1
      link.save
    end

    return link
  end
end
